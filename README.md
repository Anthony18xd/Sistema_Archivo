# Sistema Integral de Gestión y Control de Archivo Físico Municipal

Sistema web para la gestión, control, búsqueda y préstamo de documentos físicos del archivo municipal.

## Requisitos

- **PHP** 7.4+ (con extensiones `pdo_mysql`, `mysqli`, `mysqlnd`)
- **MySQL** / **MariaDB** 5.7+ (con `utf8mb4`)
- **Composer** (solo si deseas usar dependencias; el proyecto no tiene dependencias obligatorias)

## Estructura del proyecto

```
Sistema_Archivo/
├── api/            # Endpoints auxiliares (AJAX)
├── auditoria/      # Registro de auditoría del sistema
├── auth/           # Inicio y cierre de sesión
├── config/         # Configuración principal (BD, rutas)
├── controllers/    # Controladores (pueden residir en los .php de cada módulo)
├── css/            # Hojas de estilo
├── database/       # schema.sql y seed.sql (base de datos)
├── documentos/     # Registrar, buscar, ver, editar y listar documentos
├── includes/       # Conexión a BD, autenticación, helpers, auditoría
├── models/         # Lógica de acceso a datos (Documento, Prestamo, Usuario, etc.)
├── prestamos/      # Registrar, devolver y listar préstamos
├── reportes/       # Reportes del sistema
├── ubicaciones/    # Consulta y administración de ambientes/estantes/cajas
├── usuarios/       # Gestión de usuarios
├── views/          # Layouts (login y principal)
├── index.php       # Dashboard principal
└── install.php     # Instalador de la base de datos
```

## Pasos para ejecutar el proyecto

### 1. Clonar el repositorio

```bash
git clone https://github.com/Anthony18xd/Sistema_Archivo.git
cd Sistema_Archivo
```

### 2. Configurar la base de datos

Edita `config/config.php` con los datos de tu servidor MySQL/MariaDB:

```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'archivo_municipal');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 3. Instalar la base de datos

Tienes dos opciones equivalentes:

**Opción A — Instalador web:** inicia el servidor (paso 4) y abre `http://172.40.15.59:8080/install.php`, luego pulsa **Ejecutar Instalación**. Este script crea la base de datos y carga los datos iniciales.

**Opción B — Manual con línea de comandos:**

```bash
mysql -h 127.0.0.1 -u root -p -e "CREATE DATABASE archivo_municipal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -h 127.0.0.1 -u root -p archivo_municipal < database/schema.sql
mysql -h 127.0.0.1 -u root -p archivo_municipal < database/seed.sql
```

> Después de instalar, elimina `install.php` por seguridad.

### 4. Iniciar el servidor

**Opción A — Servidor PHP integrado (recomendado):**

```bash
# php -S localhost:8080 -t .
php -S 172.40.15.59:8080 -t . server_router.php
```

> El router `server_router.php` es obligatorio: bloquea el acceso web a archivos
> sensibles (`.sql`, `.log`, `config/`, `includes/`, `models/`, `database/`,
> `vendor/`, `.git/`, `composer.*`, `install.php`) porque el servidor integrado
> de PHP no aplica `.htaccess`.

**Opción B — XAMPP / WAMP / LAMP:** copia la carpeta del proyecto dentro de `htdocs` (XAMPP) o `www` (WAMP) y accede desde `http://172.40.15.59/Sistema_Archivo` (`http://localhost/Sistema_Archivo` de forma local).

### 5. Acceder al sistema

Abre en tu navegador: `http://172.40.15.59:8080`

**Credenciales por defecto:**

| Usuario | Contraseña | Rol |
|---------|-----------|-----|
| `admin` | `admin123` | Administrador (acceso total) |

## Funcionalidades

- **Documentos:** registro, búsqueda, edición, detalle e inventario.
- **Préstamos:** registrar, devolver y control de vencidos.
- **Ubicaciones:** administración de ambientes, estantes, niveles y cajas.
- **Reportes:** inventario general, por año, por área, por tipo, préstamos vencidos y por ubicación.
- **Usuarios y roles:** administrador, archivista y consulta.
- **Auditoría:** registro de todas las acciones importantes del sistema.

## Nota importante

Este proyecto fue generado por **Anthony18xd**. Los cambios locales (áreas, tipos de documento y correcciones de tildes) se reflejan en la base de datos y en `database/seed.sql`.