<?php
/**
 * ARCHIVO: config/config.php
 * CONFIGURACION PRINCIPAL DEL SISTEMA
 */

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'archivo_municipal');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('SITE_URL', '');
define('SITE_NAME', 'Sistema de Archivo Municipal');
define('SITE_VERSION', '1.0.0');

define('SESSION_LIFETIME', 3600);

define('PATH_ROOT', dirname(__DIR__));
define('PATH_CONFIG', PATH_ROOT . '/config');
define('PATH_INCLUDES', PATH_ROOT . '/includes');
define('PATH_MODELS', PATH_ROOT . '/models');
define('PATH_CONTROLLERS', PATH_ROOT . '/controllers');
define('PATH_VIEWS', PATH_ROOT . '/views');
define('PATH_AUTH', PATH_ROOT . '/auth');
define('PATH_CSS', PATH_ROOT . '/css');
define('PATH_JS', PATH_ROOT . '/js');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// ---- Cabeceras de seguridad (funciona tambien con php -S) ----
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; object-src 'none'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");

$esHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
if ($esHttps) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// ---- Hardening de sesion ----
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => $esHttps,
]);

session_start();

// ---- Timeout por inactividad: si supera SESSION_LIFETIME, se cierra la sesion ----
if (!empty($_SESSION['user_id'])) {
    $ahora = time();
    if (!isset($_SESSION['last_activity']) || ($ahora - (int) $_SESSION['last_activity']) > SESSION_LIFETIME) {
        session_unset();
        session_destroy();
        session_start();
    } else {
        $_SESSION['last_activity'] = $ahora;
    }
}

require_once PATH_INCLUDES . '/database.php';
require_once PATH_INCLUDES . '/helpers.php';
require_once PATH_INCLUDES . '/audit.php';
require_once PATH_INCLUDES . '/auth.php';

spl_autoload_register(function ($class) {
    $models = [
        PATH_MODELS . '/' . $class . '.php',
    ];
    foreach ($models as $file) {
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
