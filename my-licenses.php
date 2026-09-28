<?php
declare(strict_types=1);

$pageTitle = 'Mis Licencias';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/src/LicenseManager.php';

$manager = new LicenseManager();
$clientUserId = (int)$currentUser['id'];
$licenses = $manager->getAllLicenses($clientUserId);
?>

<main class="main-content">
    <div class="header-bar">
        <div>
            <h1 class="page-title">🔑 Mis Licencias Adquiridas</h1>
            <p class="page-desc">Consulta el estado, la URL asignada y las fechas de vigencia de tu software.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Licencias Asignadas a <?php echo sanitize($currentUser['name']); ?></h2>
        </div>

        <?php if (empty($licenses)): ?>
            <div style="text-align: center; padding: 3rem; color: var(--text-muted);">
                <p style="font-size: 1.2rem; margin-bottom: 0.5rem;">📭 No tienes licencias registradas aún.</p>
                <p>Contacta a soporte si has realizado una compra recientemente.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Clave de Licencia</th>
                            <th>Dominio Autorizado</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Vencimiento</th>
                            <th>Días Restantes</th>
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

                            $endDateObj = new DateTime($lic['end_date']);
                            $todayObj = new DateTime($today);
                            $diff = $todayObj->diff($endDateObj);
                            $daysRemaining = $todayObj > $endDateObj ? 0 : (int)$diff->format('%r%a');

                            if ($isValidToday) {
                                $statusBadge = '<span class="badge badge-active">🟢 Activa & Vigente</span>';
                            } elseif (!$isFlagActive) {
                                $statusBadge = '<span class="badge badge-inactive">🔴 Suspendida</span>';
                            } elseif ($today < $lic['start_date']) {
                                $statusBadge = '<span class="badge badge-warning">⏳ Pendiente Inicio</span>';
                            } else {
                                $statusBadge = '<span class="badge badge-inactive">⌛ Vencida</span>';
                            }
                        ?>
                            <tr>
                                <td>
                                    <strong><?php echo sanitize($lic['product_name']); ?></strong><br>
                                    <span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo sanitize($lic['product_code']); ?></span>
                                </td>
                                <td>
                                    <code class="license-key-code"><?php echo sanitize($lic['license_key']); ?></code>
                                </td>
                                <td>
                                    <span style="color: #6366f1; font-weight: 500;"><?php echo sanitize($lic['domain_url']); ?></span>
                                </td>
                                <td>📅 <?php echo $lic['start_date']; ?></td>
                                <td>🏁 <?php echo $lic['end_date']; ?></td>
                                <td>
                                    <?php if ($isValidToday): ?>
                                        <span style="color: var(--accent-success); font-weight: 600;"><?php echo $daysRemaining; ?> días</span>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">0 días</span>
                                    <?php endif; ?>
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
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
