<?php
declare(strict_types=1);

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../config/database.php';
            
            $dsnWithoutDb = "mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}";
            
            try {
                // Intentar conectar al servidor MySQL
                $pdo = new PDO($dsnWithoutDb, $config['username'], $config['password'], $config['options']);
                
                // Asegurar que la base de datos exista
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['dbname']}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                $pdo->exec("USE `{$config['dbname']}`;");

                // Verificar si existen tablas, si no, ejecutar schema automatizado
                $tablesQuery = $pdo->query("SHOW TABLES LIKE 'users'");
                if ($tablesQuery->rowCount() === 0) {
                    self::initSchema($pdo);
                }

                self::$instance = $pdo;
            } catch (PDOException $e) {
                die("Error de conexión a la Base de Datos: " . $e->getMessage() . 
                    "<br><br>Por favor verifica que MySQL esté iniciado en XAMPP y los datos en config/database.php sean correctos.");
            }
        }

        return self::$instance;
    }

    private static function initSchema(PDO $pdo): void {
        $sqlPath = __DIR__ . '/../database.sql';
        if (file_exists($sqlPath)) {
            $sql = file_get_contents($sqlPath);
            $pdo->exec($sql);
        }
    }
}
