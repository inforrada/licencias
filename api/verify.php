<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../src/LicenseManager.php';

// Habilitar CORS para permitir llamadas API desde cualquier origen / cliente
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// Responder inmediatamente a peticiones preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Obtener parámetros desde GET, POST o JSON body
$rawInput = file_get_contents('php://input');
$jsonInput = json_decode($rawInput, true) ?? [];

$licenseKey = $_REQUEST['license_key'] ?? $_REQUEST['key'] ?? $jsonInput['license_key'] ?? $jsonInput['key'] ?? null;
$domain = $_REQUEST['domain'] ?? $_REQUEST['url'] ?? $jsonInput['domain'] ?? $jsonInput['url'] ?? null;
$productCode = $_REQUEST['product_code'] ?? $_REQUEST['product'] ?? $jsonInput['product_code'] ?? $jsonInput['product'] ?? null;

if (empty($licenseKey)) {
    json_response([
        'active' => false,
        'valid' => false,
        'error' => 'MISSING_LICENSE_KEY',
        'message' => 'Se requiere la clave de licencia (parámetro: license_key o key).'
    ], 400);
}

try {
    $manager = new LicenseManager();
    $result = $manager->verifyApi((string)$licenseKey, $domain ? (string)$domain : null, $productCode ? (string)$productCode : null);

    // Retorna código HTTP 200 si es activa y válida, 400/403 si está expirada o inválida
    $statusCode = $result['valid'] ? 200 : 400;
    json_response($result, $statusCode);

} catch (Throwable $e) {
    json_response([
        'active' => false,
        'valid' => false,
        'error' => 'SERVER_ERROR',
        'message' => 'Error al procesar la verificación: ' . $e->getMessage()
    ], 500);
}
