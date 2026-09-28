<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

class Auth {
    public static function login(string $email, string $password): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => strtolower(trim($email))]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            return true;
        }

        return false;
    }

    public static function check(): bool {
        return isset($_SESSION['user_id']);
    }

    public static function user(): ?array {
        if (!self::check()) {
            return null;
        }
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'role' => $_SESSION['user_role'],
        ];
    }

    public static function isAdmin(): bool {
        return self::check() && $_SESSION['user_role'] === 'admin';
    }

    public static function isClient(): bool {
        return self::check() && $_SESSION['user_role'] === 'client';
    }

    public static function requireAuth(): void {
        if (!self::check()) {
            redirect('login.php');
        }
    }

    public static function requireAdmin(): void {
        self::requireAuth();
        if (!self::isAdmin()) {
            http_response_code(403);
            die("Acceso Denegado. Se requieren permisos de Administrador.");
        }
    }

    public static function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_unset();
        session_destroy();
        redirect('login.php');
    }
}
