<?php
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/src/Auth.php';

if (Auth::check()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (Auth::login($email, $password)) {
        redirect('dashboard.php');
    } else {
        $error = 'Credenciales inválidas. Por favor intenta de nuevo.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Servidor de Licencias</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">

<div class="login-card">
    <div class="login-header">
        <div class="brand-icon" style="margin: 0 auto 1rem auto; width: 50px; height: 50px; font-size: 1.5rem;">🔑</div>
        <h1 class="brand-title" style="font-size: 1.5rem;">Servidor de Licencias</h1>
        <p class="brand-subtitle">Ingresa a tu panel de gestión de software</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <span>⚠️</span> <?php echo sanitize($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="form-group">
            <label class="form-label" for="email">Correo Electrónico</label>
            <input type="email" id="email" name="email" class="form-control" required placeholder="correo@ejemplo.com">
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Contraseña</label>
            <input type="password" id="password" name="password" class="form-control" required placeholder="••••••••">
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem; padding: 0.75rem;">
            Iniciar Sesión →
        </button>
    </form>

    <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--bg-card-border);">
        <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.75rem; text-align: center;">
            💡 <strong>Cuentas de prueba predeterminadas:</strong>
        </p>
        
        <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.8rem;">
            <div style="background: rgba(255,255,255,0.03); padding: 0.5rem 0.75rem; border-radius: var(--radius-sm); border: 1px solid var(--bg-card-border); cursor: pointer;"
                 onclick="document.getElementById('email').value='admin@licencias.com'; document.getElementById('password').value='123456';">
                <span class="badge badge-active" style="float: right;">Admin</span>
                <strong>Email:</strong> admin@licencias.com<br>
                <strong>Pass:</strong> 123456
            </div>
            
            <div style="background: rgba(255,255,255,0.03); padding: 0.5rem 0.75rem; border-radius: var(--radius-sm); border: 1px solid var(--bg-card-border); cursor: pointer;"
                 onclick="document.getElementById('email').value='cliente@alfa.com'; document.getElementById('password').value='123456';">
                <span class="badge badge-warning" style="float: right;">Cliente</span>
                <strong>Email:</strong> cliente@alfa.com<br>
                <strong>Pass:</strong> 123456
            </div>
        </div>
    </div>
</div>

</body>
</html>
