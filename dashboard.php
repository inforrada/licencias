<?php
declare(strict_types=1);

$pageTitle = 'Panel Principal';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/src/LicenseManager.php';

$manager = new LicenseManager();
$isAdmin = Auth::isAdmin();

if ($isAdmin) {
    $licenses = $manager->getAllLicenses();
    $products = $manager->getAllProducts();
    $clients = $manager->getAllClients();

    $activeCount = count(array_filter($licenses, fn($l) => (int)$l['is_active'] === 1));
    $productCount = count($products);
    $clientCount = count(array_filter($clients, fn($u) => $u['role'] === 'client'));
} else {
    $clientUserId = (int)$currentUser['id'];
    $licenses = $manager->getAllLicenses($clientUserId);
    $activeCount = count(array_filter($licenses, fn($l) => (int)$l['is_active'] === 1));
}
?>

<main class="main-content">
    <div class="header-bar">
        <div>
            <h1 class="page-title">
                <?php echo $isAdmin ? '🛡️ Panel de Administración' : '👤 Mi Portal de Licencias'; ?>
            </h1>
            <p class="page-desc">
                <?php echo $isAdmin 
                    ? 'Gestiona licencias, vigencias por dominio y productos.' 
                    : 'Consulta el estado y la vigencia de tus licencias adquiridas.'; ?>
            </p>
        </div>

        <?php if ($isAdmin): ?>
            <button class="btn btn-primary" data-modal-target="modal-new-license">
                <span>➕</span> Nueva Licencia
            </button>
        <?php endif; ?>
    </div>

    <!-- TARJETAS DE ESTADÍSTICAS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Licencias</div>
            <div class="stat-value"><?php echo count($licenses); ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Licencias Activas</div>
            <div class="stat-value" style="color: var(--accent-success);"><?php echo $activeCount; ?></div>
        </div>

        <?php if ($isAdmin): ?>
            <div class="stat-card">
                <div class="stat-label">Productos Registrar</div>
                <div class="stat-value"><?php echo $productCount; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Clientes Registrados</div>
                <div class="stat-value"><?php echo $clientCount; ?></div>
            </div>
        <?php else: ?>
            <div class="stat-card">
                <div class="stat-label">Inactivas / Vencidas</div>
                <div class="stat-value" style="color: var(--accent-danger);">
                    <?php echo count($licenses) - $activeCount; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- SECCIÓN PRUEBA API INTERACTIVA (Solo Admins) -->
    <?php if ($isAdmin): ?>
        <div class="card" style="border-left: 4px solid var(--accent-primary);">
            <div class="card-header">
                <div>
                    <h2 class="card-title">⚡ Verificador Live de API (`/api/verify.php`)</h2>
                    <p class="page-desc">Simula una petición remota de un software o sitio cliente para validar su clave de licencia.</p>
                </div>
            </div>

            <form id="api-tester-form" class="form-row" style="align-items: flex-end;">
                <div class="form-group" style="flex: 2; margin-bottom: 0;">
                    <label class="form-label" for="test-key">Clave de Licencia</label>
                    <input type="text" id="test-key" class="form-control" placeholder="Ej: LIC-ALFA-ERP-2026-ACTIVE" required 
                           value="<?php echo !empty($licenses) ? sanitize($licenses[0]['license_key']) : ''; ?>">
                </div>

                <div class="form-group" style="flex: 1.5; margin-bottom: 0;">
                    <label class="form-label" for="test-domain">Dominio (URL)</label>
                    <input type="text" id="test-domain" class="form-control" placeholder="Ej: alfa-corp.com" 
                           value="<?php echo !empty($licenses) ? sanitize($licenses[0]['domain_url']) : ''; ?>">
                </div>

                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                    <label class="form-label" for="test-product">Cod. Producto</label>
                    <input type="text" id="test-product" class="form-control" placeholder="Ej: ERP-CLOUD" 
                           value="<?php echo !empty($licenses) ? sanitize($licenses[0]['product_code']) : ''; ?>">
                </div>

                <button type="submit" class="btn btn-primary" style="margin-bottom: 0;">
                    Probar API →
                </button>
            </form>

            <div id="api-response-container" style="display: none; margin-top: 1.5rem; background: rgba(0,0,0,0.5); border-radius: var(--radius-md); padding: 1rem; border: 1px solid var(--bg-card-border);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <span style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted);">RESPUESTA JSON DEL SERVIDOR:</span>
                    <span id="api-response-code" class="badge"></span>
                </div>
                <pre id="api-response-body" style="font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; color: #a5b4fc; overflow-x: auto; white-space: pre-wrap;"></pre>
            </div>
        </div>
    <?php endif; ?>

    <!-- TABLA DE LICENCIAS -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <?php echo $isAdmin ? '📜 Todas las Licencias Registradas' : '📜 Lista de mis Licencias'; ?>
            </h2>
            <?php if ($isAdmin): ?>
                <a href="licenses.php" class="btn btn-secondary btn-sm">Ver todas y administrar →</a>
            <?php endif; ?>
        </div>

        <?php if (empty($licenses)): ?>
            <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
                No hay licencias registradas actualmente.
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Clave Licencia</th>
                            <th>Producto</th>
                            <?php if ($isAdmin): ?><th>Cliente</th><?php endif; ?>
                            <th>Dominio Autorizado</th>
                            <th>Vigencia (Inicio - Fin)</th>
                            <th>Estado hoy</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $today = date('Y-m-d');
                        foreach ($licenses as $lic): 
                            $isFlagActive = ((int)$lic['is_active'] === 1);
                            $isWithinDates = ($today >= $lic['start_date'] && $today <= $lic['end_date']);
                            $isValidToday = ($isFlagActive && $isWithinDates);

                            // Status badge helper
                            if ($isValidToday) {
                                $statusBadge = '<span class="badge badge-active">🟢 Activa & Vigente</span>';
                            } elseif (!$isFlagActive) {
                                $statusBadge = '<span class="badge badge-inactive">🔴 Desactivada</span>';
                            } elseif ($today < $lic['start_date']) {
                                $statusBadge = '<span class="badge badge-warning">⏳ Pendiente Inicio</span>';
                            } else {
                                $statusBadge = '<span class="badge badge-inactive">⌛ Expirada</span>';
                            }
                        ?>
                            <tr>
                                <td>
                                    <code class="license-key-code"><?php echo sanitize($lic['license_key']); ?></code>
                                </td>
                                <td>
                                    <strong><?php echo sanitize($lic['product_name']); ?></strong><br>
                                    <span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo sanitize($lic['product_code']); ?></span>
                                </td>
                                <?php if ($isAdmin): ?>
                                    <td>
                                        <div><?php echo sanitize($lic['user_name']); ?></div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo sanitize($lic['user_email']); ?></div>
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <span style="color: #6366f1; font-weight: 500;"><?php echo sanitize($lic['domain_url']); ?></span>
                                </td>
                                <td>
                                    <div><strong>📅 <?php echo $lic['start_date']; ?></strong> al <strong><?php echo $lic['end_date']; ?></strong></div>
                                </td>
                                <td><?php echo $statusBadge; ?></td>
                                <td>
                                    <button class="btn btn-secondary btn-sm btn-copy" data-copy="<?php echo sanitize($lic['license_key']); ?>">
                                        📋 Copiar Key
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- GUÍA DE INTEGRACIÓN PARA CLIENTES -->
    <?php if (!$isAdmin): ?>
        <div class="card" style="border-left: 4px solid var(--accent-success);">
            <div class="card-header">
                <h2 class="card-title">🔌 Guía de Integración API para tu Aplicación</h2>
            </div>
            <p class="page-desc" style="margin-bottom: 1rem;">
                Puedes verificar la validez de tu licencia en tiempo real llamando a nuestro servidor de licencias vía HTTP GET/POST:
            </p>
            
            <label class="form-label">Ejemplo de consulta HTTP GET:</label>
            <pre style="background: rgba(0,0,0,0.5); padding: 1rem; border-radius: var(--radius-md); font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; color: #6ee7b7; overflow-x: auto; margin-bottom: 1rem;">
GET http://localhost/licencias/api/verify.php?license_key=TU_LICENCIA_KEY&domain=tu-dominio.com&product_code=CODIGO_PRODUCTO
            </pre>

            <label class="form-label">Ejemplo de integración PHP 8.3 en tu software cliente:</label>
            <pre style="background: rgba(0,0,0,0.5); padding: 1rem; border-radius: var(--radius-md); font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; color: #a5b4fc; overflow-x: auto;">
&lt;?php
$licenseKey = "<?php echo !empty($licenses) ? $licenses[0]['license_key'] : 'TU_LICENCIA'; ?>";
$domain = $_SERVER['HTTP_HOST'] ?? "tu-dominio.com";

$apiUrl = "http://localhost/licencias/api/verify.php?" . http_build_query([
    'license_key' => $licenseKey,
    'domain' => $domain
]);

$response = @file_get_contents($apiUrl);
$data = json_decode($response, true);

if ($data && !empty($data['active'])) {
    echo "Licencia Válida hasta: " . $data['validity_range']['end_date'];
} else {
    die("Licencia inválida o expirada: " . ($data['message'] ?? 'Error de conexion'));
}
            </pre>
        </div>
    <?php endif; ?>
</main>

<!-- MODAL CREAR LICENCIA (SOLO ADMIN) -->
<?php if ($isAdmin): ?>
<div class="modal-backdrop" id="modal-new-license">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">➕ Generar Nueva Licencia</h2>
            <button class="modal-close">&times;</button>
        </div>
        <form method="POST" action="licenses.php">
            <input type="hidden" name="action" value="create">
            
            <div class="form-group">
                <label class="form-label" for="user_id">Cliente / Usuario</label>
                <select name="user_id" id="user_id" class="form-control" required>
                    <?php foreach ($clients as $c): ?>
                        <?php if ($c['role'] === 'client'): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo sanitize($c['name']); ?> (<?php echo sanitize($c['email']); ?>)</option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="product_id">Producto</label>
                <select name="product_id" id="product_id" class="form-control" required>
                    <?php foreach ($products as $p): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo sanitize($p['name']); ?> (<?php echo sanitize($p['code']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="domain_url">Dominio / URL Autorizada</label>
                <input type="text" name="domain_url" id="domain_url" class="form-control" placeholder="ejemplo.com o https://miweb.com" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="start_date">Fecha Inicio</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="end_date">Fecha Término</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="license_key">Clave Personalizada (Opcional, dejar en blanco para autogenerar)</label>
                <input type="text" name="license_key" id="license_key" class="form-control" placeholder="LIC-XXXX-XXXX-XXXX">
            </div>

            <div class="form-group">
                <label class="form-label">
                    <input type="checkbox" name="is_active" value="1" checked style="margin-right: 0.5rem;"> 
                    Marcar como Activa inmediatamente
                </label>
                <p style="font-size: 0.75rem; color: var(--accent-warning); margin-top: 0.25rem;">
                    * Nota: Si ya existe otra licencia activa para el mismo dominio y producto en el rango de fechas, la licencia previa se desactivará automáticamente para asegurar que solo una esté activa.
                </p>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Licencia</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
