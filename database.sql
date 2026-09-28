-- Script de creación de Base de Datos y Datos Iniciales para Servidor de Licencias PHP 8.3
-- Compatible con MySQL 5.7+ / 8.0+ / MariaDB (XAMPP)

CREATE DATABASE IF NOT EXISTS `licencias_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `licencias_db`;

-- --------------------------------------------------------
-- Estructura de Tabla: `users`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'client') NOT NULL DEFAULT 'client',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estructura de Tabla: `products`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estructura de Tabla: `licenses`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `licenses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `license_key` VARCHAR(100) NOT NULL,
  `domain_url` VARCHAR(255) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `notes` VARCHAR(255) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  INDEX `idx_verify` (`license_key`, `domain_url`, `is_active`),
  INDEX `idx_product_domain` (`product_id`, `domain_url`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Insertar Datos Iniciales (Semilla de prueba)
-- Contraseña para todos los usuarios predeterminados: 123456
-- Hash PHP: $2y$10$wT8Kz50Jb5tUuR9F8W4dNeU02V5xU0W1x9Wz4s2R1w9Wz4s2R1w9W -> Usaremos password_hash de PHP ('123456')
-- Hash real para '123456': $2y$10$8.UnVuG9HHgffUDAlk8qfOuVGkqRzgVym502LN6z4dE7yXo/vX4v2
-- --------------------------------------------------------

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`) VALUES
(1, 'Administrador Principal', 'admin@licencias.com', '$2y$10$8.UnVuG9HHgffUDAlk8qfOuVGkqRzgVym502LN6z4dE7yXo/vX4v2', 'admin'),
(2, 'Segundo Administrador', 'admin2@licencias.com', '$2y$10$8.UnVuG9HHgffUDAlk8qfOuVGkqRzgVym502LN6z4dE7yXo/vX4v2', 'admin'),
(3, 'Cliente Empresa Alfa', 'cliente@alfa.com', '$2y$10$8.UnVuG9HHgffUDAlk8qfOuVGkqRzgVym502LN6z4dE7yXo/vX4v2', 'client'),
(4, 'Cliente Beta Solutions', 'cliente@beta.com', '$2y$10$8.UnVuG9HHgffUDAlk8qfOuVGkqRzgVym502LN6z4dE7yXo/vX4v2', 'client')
ON DUPLICATE KEY UPDATE `id`=`id`;

INSERT INTO `products` (`id`, `name`, `code`, `description`, `status`) VALUES
(1, 'Sistema ERP Cloud', 'ERP-CLOUD', 'Sistema de gestión empresarial para PYMES', 'active'),
(2, 'Plugin SEO Pro WordPress', 'SEO-WP-PRO', 'Plugin avanzado de posicionamiento web', 'active'),
(3, 'Modulo Facturación API', 'FACTURA-API', 'Servicio web para generación de comprobantes', 'active')
ON DUPLICATE KEY UPDATE `id`=`id`;

INSERT INTO `licenses` (`id`, `user_id`, `product_id`, `license_key`, `domain_url`, `is_active`, `start_date`, `end_date`, `notes`) VALUES
(1, 3, 1, 'LIC-ALFA-ERP-2026-ACTIVE', 'alfa-corp.com', 1, '2026-01-01', '2026-12-31', 'Licencia anual activa 2026'),
(2, 3, 1, 'LIC-ALFA-ERP-2025-OLD', 'alfa-corp.com', 0, '2025-01-01', '2025-12-31', 'Licencia año anterior (desactivada por renovación)'),
(3, 3, 2, 'LIC-ALFA-SEO-2026-ACTIVE', 'alfa-corp.com', 1, '2026-03-01', '2027-02-28', 'Licencia SEO anual'),
(4, 4, 1, 'LIC-BETA-ERP-2026-EXPIRED', 'beta-solutions.net', 1, '2025-06-01', '2026-06-01', 'Licencia vencida'),
(5, 4, 3, 'LIC-BETA-FACT-2026-ACTIVE', 'beta-solutions.net', 1, '2026-01-01', '2026-12-31', 'Modulo API activo')
ON DUPLICATE KEY UPDATE `id`=`id`;
