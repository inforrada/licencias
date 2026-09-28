<?php
declare(strict_types=1);

$pageTitle = 'Gestión de Licencias';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/src/LicenseManager.php';

Auth::requireAdmin();

$manager = new LicenseManager();
$message = '';
$error = '';

// Procesar acciones de formulario POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        try {
            $id = $manager->createLicense([
                'user_id' => $_POST['user_id'],
                'product_id' => $_POST['product_id'],
                'license_key' => $_POST['license_key'] ?? '',
                'domain_url' => $_POST['domain_url'],
                'start_date' => $_POST['start_date'],
                'end_date' => $_POST['end_date'],
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'notes' => $_POST['notes'] ?? '',
            ]);
            $message = "Licencia creada exitosamente con ID #$id.";
        } catch (Throwable $e) {
            $error = "Error al crear la licencia: " . $e->getMessage();
        }
    } elseif ($action === 'update') {
        try {
            $id = (int)$_POST['license_id'];
            $manager->updateLicense($id, [
                'user_id' => $_POST['user_id'],
                'product_id' => $_POST['product_id'],
                'license_key' => $_POST['license_key'],
                'domain_url' => $_POST['domain_url'],
                'start_date' => $_POST['start_date'],
                'end_date' => $_POST['end_date'],
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'notes' => $_POST['notes'] ?? '',
            ]);
            $message = "Licencia #$id actualizada correctamente.";
        } catch (Throwable $e) {
            $error = "Error al actualizar la licencia: " . $e->getMessage();
        }
    } elseif ($action === 'toggle_status') {
        try {
            $id = (int)$_POST['license_id'];
            $status = (int)$_POST['status'] === 1;
            $manager->toggleStatus($id, $status);
            $message = "El estado de la licencia #$id se ha modificado a " . ($status ? 'ACTIVA' : 'INACTIVA') . ".";
        } catch (Throwable $e) {
            $error = "Error al cambiar estado: " . $e->getMessage();
        }
    } elseif ($action === 'delete') {
        try {
            $id = (int)$_POST['license_id'];
            $manager->deleteLicense($id);
            $message = "Licencia #$id eliminada correctamente.";
        } catch (Throwable $e) {
            $error = "Error al eliminar licencia: " . $e->getMessage();
        }
    }
}

$search = $_GET['search'] ?? null;
$licenses = $manager->getAllLicenses(null, $search);
$products = $manager->getAllProducts();
$clients = $manager->getAllClients();
?>

<main class="main-content">
    <div class="header-bar">
        <div>
            <h1 class="page-title">📜 Administración de Licencias</h1>
            <p class="page-desc">Crea, edita o desactiva licencias. Recuerda que solo 1 licencia puede estar activa para el mismo dominio y producto en un rango de fechas.</p>
        </div>

        <button class="btn btn-primary" data-modal-target="modal-new-license">
            <span>➕</span> Crear Licencia
        </button>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success">
            <span>✅</span> <?php echo sanitize($message); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <span>⚠️</span> <?php echo sanitize($error); ?>
        </div>
    <?php endif; ?>

    <!-- BARRA DE BÚSQUEDA Y FILTRADO -->
    <div class="card" style="padding: 1rem 1.5rem; margin-bottom: 1.5rem;">
        <form method="GET" action="licenses.php" style="display: flex; gap: 1rem; align-items: center;">
            <input type="text" name="search" class="form-control" placeholder="Buscar por Clave, Dominio, Cliente o Producto..." value="<?php echo sanitize($search ?? ''); ?>" style="flex: 1;">
            <button type="submit" class="btn btn-primary">🔍 Buscar</button>
            <?php if (!empty($search)): ?>
                <a href="licenses.php" class="btn btn-secondary">Limpiar</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- LISTADO COMPLETO DE LICENCIAS -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Todas las Licencias (<?php echo count($licenses); ?>)</h2>
        </div>

        <?php if (empty($licenses)): ?>
            <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
                No se encontraron licencias que coincidan con la búsqueda.
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID / Clave</th>
                            <th>Producto</th>
                            <th>Cliente</th>
                            <th>Dominio Autorizado</th>
                            <th>Vigencia (Inicio - Fin)</th>
                            <th>Estado Actual</th>
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

                            if ($isValidToday) {
                                $statusBadge = '<span class="badge badge-active">🟢 Activa</span>';
                            } elseif (!$isFlagActive) {
                                $statusBadge = '<span class="badge badge-inactive">🔴 Inactiva</span>';
                            } elseif ($today < $lic['start_date']) {
                                $statusBadge = '<span class="badge badge-warning">⏳ Próxima</span>';
                            } else {
                                $statusBadge = '<span class="badge badge-inactive">⌛ Expirada</span>';
                            }
                        ?>
                            <tr>
                                <td>
                                    <strong style="color: var(--text-dim);">#<?php echo $lic['id']; ?></strong><br>
                                    <code class="license-key-code"><?php echo sanitize($lic['license_key']); ?></code>
                                </td>
                                <td>
                                    <strong><?php echo sanitize($lic['product_name']); ?></strong><br>
                                    <span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo sanitize($lic['product_code']); ?></span>
                                </td>
                                <td>
                                    <div><?php echo sanitize($lic['user_name']); ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo sanitize($lic['user_email']); ?></div>
                                </td>
                                <td>
                                    <span style="color: #6366f1; font-weight: 500;"><?php echo sanitize($lic['domain_url']); ?></span>
                                </td>
                                <td>
                                    <div style="font-size: 0.85rem;"><strong>📅 <?php echo $lic['start_date']; ?></strong></div>
                                    <div style="font-size: 0.85rem;"><strong>🏁 <?php echo $lic['end_date']; ?></strong></div>
                                </td>
                                <td><?php echo $statusBadge; ?></td>
                                <td>
                                    <div style="display: flex; gap: 0.35rem; align-items: center;">
                                        <!-- TOGGLE ACTIVAR / DESACTIVAR -->
                                        <form method="POST" action="licenses.php" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="license_id" value="<?php echo $lic['id']; ?>">
                                            <input type="hidden" name="status" value="<?php echo $isFlagActive ? '0' : '1'; ?>">
                                            <button type="submit" class="btn btn-secondary btn-sm" title="<?php echo $isFlagActive ? 'Desactivar' : 'Activar'; ?>">
                                                <?php echo $isFlagActive ? '⏸️ Desactivar' : '▶️ Activar'; ?>
                                            </button>
                                        </form>

                                        <!-- BOTÓN EDITAR (MODAL) -->
                                        <button class="btn btn-secondary btn-sm" data-modal-target="modal-edit-license-<?php echo $lic['id']; ?>">
                                            ✏️
                                        </button>

                                        <!-- BORRAR -->
                                        <form method="POST" action="licenses.php" style="display: inline;" onsubmit="return confirm('¿Seguro que deseas eliminar esta licencia?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="license_id" value="<?php echo $lic['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                                        </form>
                                    </div>

                                    <!-- MODAL EDITAR PARA CADA LICENCIA -->
                                    <div class="modal-backdrop" id="modal-edit-license-<?php echo $lic['id']; ?>">
                                        <div class="modal">
                                            <div class="modal-header">
                                                <h2 class="modal-title">✏️ Editar Licencia #<?php echo $lic['id']; ?></h2>
                                                <button class="modal-close">&times;</button>
                                            </div>
                                            <form method="POST" action="licenses.php">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="license_id" value="<?php echo $lic['id']; ?>">

                                                <div class="form-group">
                                                    <label class="form-label">Cliente / Usuario</label>
                                                    <select name="user_id" class="form-control" required>
                                                        <?php foreach ($clients as $c): ?>
                                                            <?php if ($c['role'] === 'client'): ?>
                                                                <option value="<?php echo $c['id']; ?>" <?php echo $c['id'] == $lic['user_id'] ? 'selected' : ''; ?>>
                                                                    <?php echo sanitize($c['name']); ?> (<?php echo sanitize($c['email']); ?>)
                                                                </option>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="form-group">
                                                    <label class="form-label">Producto</label>
                                                    <select name="product_id" class="form-control" required>
                                                        <?php foreach ($products as $p): ?>
                                                            <option value="<?php echo $p['id']; ?>" <?php echo $p['id'] == $lic['product_id'] ? 'selected' : ''; ?>>
                                                                <?php echo sanitize($p['name']); ?> (<?php echo sanitize($p['code']); ?>)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="form-group">
                                                    <label class="form-label">Clave de Licencia</label>
                                                    <input type="text" name="license_key" class="form-control" value="<?php echo sanitize($lic['license_key']); ?>" required>
                                                </div>

                                                <div class="form-group">
                                                    <label class="form-label">Dominio / URL</label>
                                                    <input type="text" name="domain_url" class="form-control" value="<?php echo sanitize($lic['domain_url']); ?>" required>
                                                </div>

                                                <div class="form-row">
                                                    <div class="form-group">
                                                        <label class="form-label">Fecha Inicio</label>
                                                        <input type="date" name="start_date" class="form-control" value="<?php echo $lic['start_date']; ?>" required>
                                                    </div>

                                                    <div class="form-group">
                                                        <label class="form-label">Fecha Término</label>
                                                        <input type="date" name="end_date" class="form-control" value="<?php echo $lic['end_date']; ?>" required>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="form-label">
                                                        <input type="checkbox" name="is_active" value="1" <?php echo $isFlagActive ? 'checked' : ''; ?>> 
                                                        Licencia Activa
                                                    </label>
                                                </div>

                                                <div class="form-group">
                                                    <label class="form-label">Notas / Observaciones</label>
                                                    <input type="text" name="notes" class="form-control" value="<?php echo sanitize($lic['notes'] ?? ''); ?>">
                                                </div>

                                                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                                                    <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
                                                    <button type="submit" class="btn btn-primary">Actualizar Cambios</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- MODAL CREAR LICENCIA -->
<div class="modal-backdrop" id="modal-new-license">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">➕ Nueva Licencia de Software</h2>
            <button class="modal-close">&times;</button>
        </div>
        <form method="POST" action="licenses.php">
            <input type="hidden" name="action" value="create">
            
            <div class="form-group">
                <label class="form-label">Cliente / Usuario</label>
                <select name="user_id" class="form-control" required>
                    <?php foreach ($clients as $c): ?>
                        <?php if ($c['role'] === 'client'): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo sanitize($c['name']); ?> (<?php echo sanitize($c['email']); ?>)</option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Producto</label>
                <select name="product_id" class="form-control" required>
                    <?php foreach ($products as $p): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo sanitize($p['name']); ?> (<?php echo sanitize($p['code']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Dominio / URL Autorizada</label>
                <input type="text" name="domain_url" class="form-control" placeholder="ejemplo.com" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Fecha Inicio</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Fecha Término</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Clave (Opcional)</label>
                <input type="text" name="license_key" class="form-control" placeholder="LIC-XXXX-XXXX-XXXX">
            </div>

            <div class="form-group">
                <label class="form-label">
                    <input type="checkbox" name="is_active" value="1" checked> 
                    Activar Inmediatamente (Desactivará otras licencias activas solapadas para el mismo producto y dominio)
                </label>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
                <button type="submit" class="btn btn-primary">Crear Licencia</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
