<?php
/**
 * ARCHIVO: server_router.php
 * ROUTER PARA EL SERVIDOR INTEGRADO DE PHP (php -S)
 *
 * El modo `php -S` NO aplica .htaccess. Este router replica esas
 * protecciones bloqueando el acceso web a archivos sensibles
 * (logs, SQL, config, vendor, .git, etc.).
 *
 * Uso:
 *   php -S 0.0.0.0:8080 -t . server_router.php
 */

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path === false || $path === '/') {
    return false;
}

$root = realpath(__DIR__);
$archivo = realpath($root . $path);

if ($archivo === false || strpos($archivo, $root) !== 0) {
    return false;
}

$rel = ltrim(substr($archivo, strlen($root)), DIRECTORY_SEPARATOR);

$dirsRestringidas = [
    'config/',
    'includes/',
    'models/',
    'database/',
    'vendor/',
    '.git/',
    'thumbnails/',
];

foreach ($dirsRestringidas as $dir) {
    if (str_starts_with($rel, $dir)) {
        http_response_code(403);
        echo 'Acceso denegado';
        return true;
    }
}

$ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
$extRestringidas = [
    'sql', 'log', 'env', 'json', 'lock', 'ini', 'md', 'yml', 'yaml',
    'pem', 'key', 'crt', 'p12', 'cnf', 'dist', 'bak', 'old', 'conf', 'properties',
];

if (in_array($ext, $extRestringidas, true)) {
    http_response_code(403);
    echo 'Acceso denegado';
    return true;
}

$archivosRestringidos = [
    'server.log',
    'composer.json',
    'composer.lock',
    'README.md',
    '.gitignore',
    'install.php',
    'server_router.php',
];

if (in_array(strtolower($rel), $archivosRestringidos, true)) {
    http_response_code(403);
    echo 'Acceso denegado';
    return true;
}

return false;