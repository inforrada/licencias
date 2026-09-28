<?php
declare(strict_types=1);

$pageTitle = 'Gestión de Productos';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/src/LicenseManager.php';

Auth::requireAdmin();

$manager = new LicenseManager();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create_product') {
        try {
            $name = $_POST['name'] ?? '';
            $code = $_POST['code'] ?? '';
            $description = $_POST['description'] ?? '';
            
            $manager->createProduct($name, $code, $description);
            $message = "Producto '$name' creado exitosamente.";
        } catch (Throwable $e) {
            $error = "Error al crear producto: " . $e->getMessage();
        }
    }
}

$products = $manager->getAllProducts();
?>

<main class="main-content">
    <div class="header-bar">
        <div>
            <h1 class="page-title">📦 Catálogo de Productos</h1>
            <p class="page-desc">Administra los productos de software para los cuales se pueden emitir licencias.</p>
        </div>

        <button class="btn btn-primary" data-modal-target="modal-new-product">
            <span>➕</span> Nuevo Producto
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
            <h2 class="card-title">Productos Registrados (<?php echo count($products); ?>)</h2>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre del Producto</th>
                        <th>Código SKU / API</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td>#<?php echo $p['id']; ?></td>
                            <td><strong><?php echo sanitize($p['name']); ?></strong></td>
                            <td><code class="license-key-code"><?php echo sanitize($p['code']); ?></code></td>
                            <td><?php echo sanitize($p['description'] ?? 'Sin descripción'); ?></td>
                            <td>
                                <span class="badge badge-active">Activo</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div class="modal-backdrop" id="modal-new-product">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">➕ Registrar Nuevo Producto</h2>
            <button class="modal-close">&times;</button>
        </div>
        <form method="POST" action="products.php">
            <input type="hidden" name="action" value="create_product">
            
            <div class="form-group">
                <label class="form-label">Nombre del Producto</label>
                <input type="text" name="name" class="form-control" placeholder="Ej: Sistema ERP Cloud Pro" required>
            </div>

            <div class="form-group">
                <label class="form-label">Código del Producto (para API)</label>
                <input type="text" name="code" class="form-control" placeholder="Ej: ERP-PRO" required>
            </div>

            <div class="form-group">
                <label class="form-label">Descripción</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Descripción breve del software..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Producto</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
