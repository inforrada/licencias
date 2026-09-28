# Servidor de Licencias de Software (PHP 8.3)

Un servidor y panel de gestión de licencias moderno, seguro y ligero en PHP 8.3 para controlar licencias de software multiproducto y multidominio.

---

## 🚀 Características Principales

1. **Gestión de Múltiples Productos y Dominios**: Soporte para diferentes productos (SKUs) y dominios web autorizados.
2. **Regla de Licencias Duplicadas**: Cada licencia puede existir de forma duplicada o histórica, pero **solamente UNA licencia estará activa para un mismo producto y dominio en un rango de fechas**. El sistema desactiva automáticamente solapamientos al activar o crear una nueva licencia.
3. **API REST de Verificación en Tiempo Real (`/api/verify.php`)**:
   - Comprueba si la licencia existe, si `is_active == 1`, si el dominio/producto coincide y si la fecha actual está dentro del rango de vigencia (`start_date` a `end_date`).
   - Retorna la vigencia exacta (rango de fechas de inicio y fin, días restantes) y el estado en JSON.
4. **Panel de Gestión Moderno (Dark Glassmorphic UI)**:
   - **Rol Administrador**: Todos los administradores tienen acceso total para crear productos, registrar clientes, emitir licencias, cambiar estados, probar la API live.
   - **Rol Cliente**: Los clientes inician sesión y pueden visualizar únicamente sus licencias activas e inactivas, sus vigencias y la guía de integración API para sus sitios.
5. **Sin Pasarela de Pago**: Diseñado exclusivamente para emisión y verificación directa sin cobros integrados.

---

## 📁 Estructura del Proyecto

```
c:\xampp\htdocs\licencias\
├── api/
│   └── verify.php        # Endpoint de Verificación API (JSON)
├── assets/
│   ├── css/style.css     # Sistema de Diseño CSS Vanilla (Dark Mode Moderno)
│   └── js/app.js         # Lógica interactiva (Modales, Copiar Claves, API Tester Live)
├── config/
│   ├── app.php           # Configuración global y funciones helper
│   └── database.php      # Datos de conexión PDO MySQL
├── includes/
│   ├── header.php        # Plantilla superior HTML
│   ├── sidebar.php       # Menú lateral dinámico según rol
│   └── footer.php        # Plantilla inferior HTML
├── src/
│   ├── Auth.php          # Control de Sesión y Roles (Admin / Client)
│   ├── Database.php      # Singleton PDO con Auto-instalación de DB
│   └── LicenseManager.php# Motor principal de licencias, fechas y API
├── clients.php           # Vista Admin: Gestión de Usuarios / Clientes
├── database.sql          # Estructura de Tablas y Datos de Prueba
├── dashboard.php         # Panel Principal y Probador Live de API
├── index.php             # Redirección inteligente según login
├── licenses.php          # Vista Admin: Crear/Editar/Desactivar Licencias
├── login.php             # Pantalla de Login con acceso rápido a credenciales
├── logout.php            # Cierre de sesión
├── my-licenses.php       # Vista Cliente: Mis Licencias y Vigencias
├── products.php          # Vista Admin: Catálogo de Productos
└── README.md             # Documentación del proyecto
```

---

## 🛢️ Instalación y Base de Datos

### Requisitos
- **PHP**: 8.3 (o superior) con extensión PDO habilitada.
- **MySQL / MariaDB**: Incluido en XAMPP.

### Pasos de Instalación
1. Colocar el proyecto en `c:\xampp\htdocs\licencias`.
2. Iniciar **Apache** y **MySQL** desde el panel de XAMPP.
3. El proyecto cuenta con **Instalación Automatizada**: Al ingresar por primera vez a `http://localhost/licencias/`, el sistema creará automáticamente la base de datos `licencias_db` y cargará el archivo `database.sql`.
4. *(Opcional)* Si prefieres importar manualmente la base de datos, puedes importar el archivo `database.sql` directamente en **phpMyAdmin** (`http://localhost/phpmyadmin/`).

---

## 🔐 Cuentas de Acceso Predeterminadas (Semilla)

| Rol | Correo Electrónico | Contraseña | Permisos |
|---|---|---|---|
| **Administrador** | `admin@licencias.com` | `123456` | Control total del servidor |
| **Administrador 2** | `admin2@licencias.com` | `123456` | Control total del servidor |
| **Cliente** | `cliente@alfa.com` | `123456` | Ver sus licencias y vigencias |
| **Cliente 2** | `cliente@beta.com` | `123456` | Ver sus licencias y vigencias |

---

## 🔌 Uso de la API REST de Verificación

**Endpoint:** `GET` o `POST` a `http://localhost/licencias/api/verify.php`

### Parámetros Aceptados:
- `license_key` (Requerido): Clave de la licencia (ej: `LIC-ALFA-ERP-2026-ACTIVE`).
- `domain` (Opcional): Dominio donde se ejecuta la app cliente (ej: `alfa-corp.com`).
- `product_code` (Opcional): Código SKU del producto (ej: `ERP-CLOUD`).

### Ejemplo de Respuesta JSON (Licencia Activa y Vigente - HTTP 200):
```json
{
  "active": true,
  "valid": true,
  "is_flag_active": true,
  "is_within_dates": true,
  "reason": "ACTIVE_AND_VALID",
  "message": "Licencia válida y actualmente activa dentro de su período de vigencia.",
  "validity_range": {
    "start_date": "2026-01-01",
    "end_date": "2026-12-31",
    "days_remaining": 94
  },
  "details": {
    "license_key": "LIC-ALFA-ERP-2026-ACTIVE",
    "product_name": "Sistema ERP Cloud",
    "product_code": "ERP-CLOUD",
    "domain_url": "alfa-corp.com",
    "client_name": "Cliente Empresa Alfa"
  }
}
```

### Ejemplo de Respuesta JSON (Licencia Expirada / Inválida - HTTP 400):
```json
{
  "active": false,
  "valid": false,
  "is_flag_active": true,
  "is_within_dates": false,
  "reason": "LICENSE_EXPIRED",
  "message": "La licencia ha expirado por fecha de término.",
  "validity_range": {
    "start_date": "2025-06-01",
    "end_date": "2026-06-01",
    "days_remaining": 0
  }
}
```

---

## 💻 Ejemplo de Integración en Cliente (PHP)

```php
<?php
$licenseKey = "LIC-ALFA-ERP-2026-ACTIVE";
$domain = $_SERVER['HTTP_HOST'] ?? "alfa-corp.com";

$apiUrl = "http://localhost/licencias/api/verify.php?" . http_build_query([
    'license_key' => $licenseKey,
    'domain' => $domain,
    'product_code' => 'ERP-CLOUD'
]);

$response = @file_get_contents($apiUrl);
$data = json_decode($response, true);

if ($data && !empty($data['active'])) {
    echo "✅ Licencia Activa. Vigencia hasta: " . $data['validity_range']['end_date'];
} else {
    die("❌ Licencia Inválida: " . ($data['message'] ?? 'Error de conexión'));
}
```
