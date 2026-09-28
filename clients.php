<?php
declare(strict_types=1);

$pageTitle = 'Gestión de Usuarios';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/src/LicenseManager.php';

Auth::requireAdmin();

$manager = new LicenseManager();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create_user') {
        try {
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'client';

            $manager->createUser($name, $email, $password, $role);
            $message = "Usuario '$name' registrado exitosamente como $role.";
        } catch (Throwable $e) {
            $error = "Error al crear usuario: " . $e->getMessage();
        }
    }
}

$users = $manager->getAllClients();
?>

<main class="main-content">
    <div class="header-bar">
        <div>
            <h1 class="page-title">👥 Administración de Usuarios</h1>
            <p class="page-desc">Crea y gestiona cuentas de administradores y clientes.</p>
        </div>

        <button class="btn btn-primary" data-modal-target="modal-new-user">
            <span>➕</span> Registrar Usuario
        </button>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success"><span>✅</span> <?php echo sanitize($message); ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><span>⚠️</span> <?php echo sanitize($error); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Usuarios Registrados (<?php echo count($users); ?>)</h2>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol / Permisos</th>
                        <th>Fecha Registro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>#<?php echo $u['id']; ?></td>
                            <td><strong><?php echo sanitize($u['name']); ?></strong></td>
                            <td><?php echo sanitize($u['email']); ?></td>
                            <td>
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span class="badge badge-active">🛡️ Administrador</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">👤 Cliente</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $u['created_at']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div class="modal-backdrop" id="modal-new-user">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">➕ Registrar Nuevo Usuario</h2>
            <button class="modal-close">&times;</button>
        </div>
        <form method="POST" action="clients.php">
            <input type="hidden" name="action" value="create_user">
            
            <div class="form-group">
                <label class="form-label">Nombre Completo / Empresa</label>
                <input type="text" name="name" class="form-control" required placeholder="Ej: Juan Pérez o Empresa XYZ">
            </div>

            <div class="form-group">
                <label class="form-label">Correo Electrónico (Login)</label>
                <input type="email" name="email" class="form-control" required placeholder="correo@ejemplo.com">
            </div>

            <div class="form-group">
                <label class="form-label">Contraseña</label>
                <input type="password" name="password" class="form-control" required placeholder="••••••••">
            </div>

            <div class="form-group">
                <label class="form-label">Rol del Usuario</label>
                <select name="role" class="form-control" required>
                    <option value="client" selected>Cliente (Solo ver sus licencias)</option>
                    <option value="admin">Administrador (Control total del sistema)</option>
                </select>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
                <button type="submit" class="btn btn-primary">Registrar Usuario</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
