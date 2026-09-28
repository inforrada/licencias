<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

class LicenseManager {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Normaliza un dominio/URL eliminando protocolo, www y rutas
     */
    public static function normalizeDomain(string $url): string {
        $url = trim($url);
        if (empty($url)) return '';

        // Agregar protocolo si no lo tiene para parse_url
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'http://' . $url;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            $host = $url;
        }

        // Quitar www. al inicio y convertir a minúsculas
        $host = strtolower($host);
        $host = preg_replace('/^www\./', '', $host);

        return $host;
    }

    /**
     * Genera una clave de licencia con formato XXXX-XXXX-XXXX-XXXX
     */
    public static function generateLicenseKey(string $prefix = 'LIC'): string {
        $parts = [];
        $parts[] = strtoupper($prefix);
        for ($i = 0; $i < 3; $i++) {
            $parts[] = strtoupper(bin2hex(random_bytes(2)));
        }
        return implode('-', $parts);
    }

    /**
     * Verifica conflictos de rango de fechas activas para un mismo producto y dominio
     */
    public function getOverlappingActiveLicenses(int $productId, string $domainUrl, string $startDate, string $endDate, ?int $excludeId = null): array {
        $normalizedDomain = self::normalizeDomain($domainUrl);

        $sql = "SELECT l.*, p.name as product_name, u.name as user_name 
                FROM licenses l
                JOIN products p ON l.product_id = p.id
                JOIN users u ON l.user_id = u.id
                WHERE l.product_id = :product_id 
                  AND l.domain_url = :domain_url 
                  AND l.is_active = 1
                  AND (l.start_date <= :end_date AND l.end_date >= :start_date)";
        
        if ($excludeId !== null) {
            $sql .= " AND l.id != :exclude_id";
        }

        $stmt = $this->db->prepare($sql);
        $params = [
            'product_id' => $productId,
            'domain_url' => $normalizedDomain,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
        if ($excludeId !== null) {
            $params['exclude_id'] = $excludeId;
        }

        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Desactiva otras licencias activas que se solapen en el rango de fechas para el mismo producto y dominio
     */
    public function autoDeactivateOverlapping(int $productId, string $domainUrl, string $startDate, string $endDate, ?int $excludeId = null): int {
        $normalizedDomain = self::normalizeDomain($domainUrl);
        
        $sql = "UPDATE licenses 
                SET is_active = 0 
                WHERE product_id = :product_id 
                  AND domain_url = :domain_url 
                  AND is_active = 1
                  AND (start_date <= :end_date AND end_date >= :start_date)";
        
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
        }

        $stmt = $this->db->prepare($sql);
        $params = [
            'product_id' => $productId,
            'domain_url' => $normalizedDomain,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
        if ($excludeId !== null) {
            $params['exclude_id'] = $excludeId;
        }

        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Crear una nueva licencia
     */
    public function createLicense(array $data): int {
        $normalizedDomain = self::normalizeDomain($data['domain_url']);
        $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;

        // Si se va a crear activa, desactivar automáticamente las activas solapadas
        if ($isActive === 1) {
            $this->autoDeactivateOverlapping((int)$data['product_id'], $normalizedDomain, $data['start_date'], $data['end_date']);
        }

        $licenseKey = !empty($data['license_key']) ? trim($data['license_key']) : self::generateLicenseKey();

        $stmt = $this->db->prepare("INSERT INTO licenses 
            (user_id, product_id, license_key, domain_url, is_active, start_date, end_date, notes) 
            VALUES (:user_id, :product_id, :license_key, :domain_url, :is_active, :start_date, :end_date, :notes)");

        $stmt->execute([
            'user_id' => (int)$data['user_id'],
            'product_id' => (int)$data['product_id'],
            'license_key' => $licenseKey,
            'domain_url' => $normalizedDomain,
            'is_active' => $isActive,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'notes' => $data['notes'] ?? null,
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Actualizar una licencia existente
     */
    public function updateLicense(int $id, array $data): bool {
        $normalizedDomain = self::normalizeDomain($data['domain_url']);
        $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 0;

        if ($isActive === 1) {
            $this->autoDeactivateOverlapping((int)$data['product_id'], $normalizedDomain, $data['start_date'], $data['end_date'], $id);
        }

        $stmt = $this->db->prepare("UPDATE licenses SET 
            user_id = :user_id,
            product_id = :product_id,
            license_key = :license_key,
            domain_url = :domain_url,
            is_active = :is_active,
            start_date = :start_date,
            end_date = :end_date,
            notes = :notes
            WHERE id = :id");

        return $stmt->execute([
            'id' => $id,
            'user_id' => (int)$data['user_id'],
            'product_id' => (int)$data['product_id'],
            'license_key' => trim($data['license_key']),
            'domain_url' => $normalizedDomain,
            'is_active' => $isActive,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Cambiar estado activo/inactivo de una licencia
     */
    public function toggleStatus(int $id, bool $activate): bool {
        if ($activate) {
            // Obtener datos de la licencia para chequear solapamiento
            $lic = $this->getLicenseById($id);
            if ($lic) {
                $this->autoDeactivateOverlapping((int)$lic['product_id'], $lic['domain_url'], $lic['start_date'], $lic['end_date'], $id);
            }
        }

        $stmt = $this->db->prepare("UPDATE licenses SET is_active = :status WHERE id = :id");
        return $stmt->execute([
            'id' => $id,
            'status' => $activate ? 1 : 0
        ]);
    }

    /**
     * Eliminar licencia
     */
    public function deleteLicense(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM licenses WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Obtener una licencia por ID
     */
    public function getLicenseById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT l.*, p.name as product_name, p.code as product_code, u.name as user_name, u.email as user_email 
            FROM licenses l
            JOIN products p ON l.product_id = p.id
            JOIN users u ON l.user_id = u.id
            WHERE l.id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Listar licencias con filtros opcionales
     */
    public function getAllLicenses(?int $userId = null, ?string $search = null): array {
        $sql = "SELECT l.*, p.name as product_name, p.code as product_code, u.name as user_name, u.email as user_email 
                FROM licenses l
                JOIN products p ON l.product_id = p.id
                JOIN users u ON l.user_id = u.id
                WHERE 1=1";
        
        $params = [];

        if ($userId !== null) {
            $sql .= " AND l.user_id = :user_id";
            $params['user_id'] = $userId;
        }

        if (!empty($search)) {
            $sql .= " AND (l.license_key LIKE :search OR l.domain_url LIKE :search OR p.name LIKE :search OR u.name LIKE :search)";
            $params['search'] = '%' . trim($search) . '%';
        }

        $sql .= " ORDER BY l.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * API VERIFICATION ENGINE
     * Verifica la vigencia de la licencia requerida por el cliente API
     */
    public function verifyApi(string $licenseKey, ?string $domain = null, ?string $productCode = null): array {
        $today = date('Y-m-d');
        
        // Buscar por clave de licencia
        $stmt = $this->db->prepare("SELECT l.*, p.name as product_name, p.code as product_code, u.name as user_name 
            FROM licenses l
            JOIN products p ON l.product_id = p.id
            JOIN users u ON l.user_id = u.id
            WHERE l.license_key = :license_key LIMIT 1");
        $stmt->execute(['license_key' => trim($licenseKey)]);
        $lic = $stmt->fetch();

        if (!$lic) {
            return [
                'active' => false,
                'valid' => false,
                'reason' => 'LICENSE_NOT_FOUND',
                'message' => 'La clave de licencia no existe en el sistema.'
            ];
        }

        // Si se provee código de producto, verificar correspondencia
        if ($productCode !== null && !empty(trim($productCode))) {
            if (strtoupper(trim($lic['product_code'])) !== strtoupper(trim($productCode))) {
                return [
                    'active' => false,
                    'valid' => false,
                    'reason' => 'PRODUCT_MISMATCH',
                    'message' => 'La licencia no pertenece al producto especificado.'
                ];
            }
        }

        // Si se provee dominio, verificar correspondencia
        if ($domain !== null && !empty(trim($domain))) {
            $normInputDomain = self::normalizeDomain($domain);
            $normStoredDomain = self::normalizeDomain($lic['domain_url']);
            
            if ($normInputDomain !== $normStoredDomain) {
                return [
                    'active' => false,
                    'valid' => false,
                    'reason' => 'DOMAIN_MISMATCH',
                    'message' => "El dominio '$normInputDomain' no coincide con el dominio registrado en la licencia ($normStoredDomain)."
                ];
            }
        }

        // Verificar si la licencia está marcada como is_active = 1
        $isFlagActive = ((int)$lic['is_active'] === 1);
        
        // Verificar rango de fechas
        $isWithinDates = ($today >= $lic['start_date'] && $today <= $lic['end_date']);

        $isValid = ($isFlagActive && $isWithinDates);

        $reason = 'ACTIVE_AND_VALID';
        $message = 'Licencia válida y actualmente activa dentro de su período de vigencia.';

        if (!$isFlagActive) {
            $reason = 'LICENSE_DEACTIVATED';
            $message = 'La licencia se encuentra desactivada administrativamente.';
        } elseif ($today < $lic['start_date']) {
            $reason = 'LICENSE_NOT_YET_ACTIVE';
            $message = 'La licencia aún no ha alcanzado su fecha de inicio de vigencia.';
        } elseif ($today > $lic['end_date']) {
            $reason = 'LICENSE_EXPIRED';
            $message = 'La licencia ha expirado por fecha de término.';
        }

        // Calcular días restantes de vigencia
        $endDateObj = new DateTime($lic['end_date']);
        $todayObj = new DateTime($today);
        $interval = $todayObj->diff($endDateObj);
        $daysRemaining = $todayObj > $endDateObj ? 0 : (int)$interval->format('%r%a');

        return [
            'active' => $isValid,
            'valid' => $isValid,
            'is_flag_active' => $isFlagActive,
            'is_within_dates' => $isWithinDates,
            'reason' => $reason,
            'message' => $message,
            'validity_range' => [
                'start_date' => $lic['start_date'],
                'end_date' => $lic['end_date'],
                'days_remaining' => $daysRemaining
            ],
            'details' => [
                'license_key' => $lic['license_key'],
                'product_name' => $lic['product_name'],
                'product_code' => $lic['product_code'],
                'domain_url' => $lic['domain_url'],
                'client_name' => $lic['user_name']
            ]
        ];
    }

    // --- MÉTODOS AUXILIARES DE PRODUCTOS Y USUARIOS ---

    public function getAllProducts(): array {
        $stmt = $this->db->query("SELECT * FROM products ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    public function createProduct(string $name, string $code, ?string $description): int {
        $stmt = $this->db->prepare("INSERT INTO products (name, code, description) VALUES (:name, :code, :description)");
        $stmt->execute([
            'name' => trim($name),
            'code' => strtoupper(trim($code)),
            'description' => $description ? trim($description) : null
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function getAllClients(): array {
        $stmt = $this->db->query("SELECT id, name, email, role, created_at FROM users ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    public function createUser(string $name, string $email, string $password, string $role = 'client'): int {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)");
        $stmt->execute([
            'name' => trim($name),
            'email' => strtolower(trim($email)),
            'password' => $hash,
            'role' => in_array($role, ['admin', 'client'], true) ? $role : 'client'
        ]);
        return (int)$this->db->lastInsertId();
    }
}
