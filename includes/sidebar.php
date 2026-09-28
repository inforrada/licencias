<?php
declare(strict_types=1);

$currentScript = basename($_SERVER['SCRIPT_NAME']);
?>
<aside class="sidebar">
    <div class="brand">
        <div class="brand-icon">🔑</div>
        <div>
            <div class="brand-title">Licencias Core</div>
            <div class="brand-subtitle">Server v1.0 (PHP 8.3)</div>
        </div>
    </div>

    <ul class="nav-menu">
        <li class="nav-item <?php echo $currentScript === 'dashboard.php' ? 'active' : ''; ?>">
            <a href="dashboard.php">
                <span>📊</span> Panel Principal
            </a>
        </li>

        <?php if (Auth::isAdmin()): ?>
            <li class="nav-item <?php echo $currentScript === 'licenses.php' ? 'active' : ''; ?>">
                <a href="licenses.php">
                    <span>📜</span> Licencias
                </a>
            </li>
            <li class="nav-item <?php echo $currentScript === 'products.php' ? 'active' : ''; ?>">
                <a href="products.php">
                    <span>📦</span> Productos
                </a>
            </li>
            <li class="nav-item <?php echo $currentScript === 'clients.php' ? 'active' : ''; ?>">
                <a href="clients.php">
                    <span>👥</span> Usuarios / Clientes
                </a>
            </li>
        <?php else: ?>
            <li class="nav-item <?php echo $currentScript === 'my-licenses.php' ? 'active' : ''; ?>">
                <a href="my-licenses.php">
                    <span>🔑</span> Mis Licencias
                </a>
            </li>
        <?php endif; ?>
    </ul>

    <div class="user-profile">
        <div class="user-info">
            <span class="user-name"><?php echo sanitize($currentUser['name']); ?></span>
            <span class="user-role"><?php echo $currentUser['role'] === 'admin' ? '🛡️ Administrador' : '👤 Cliente'; ?></span>
        </div>
        <a href="logout.php" class="btn btn-secondary btn-sm" title="Cerrar Sesión">🚪</a>
    </div>
</aside>
