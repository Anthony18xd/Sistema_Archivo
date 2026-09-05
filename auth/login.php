<?php
/**
 * ARCHIVO: auth/login.php
 * PANTALLA DE INICIO DE SESION
 */
require_once dirname(__DIR__) . '/config/config.php';

if (Auth::check()) {
    redirect(SITE_URL . '/index.php');
}

$error = '';

// ── Anti fuerza bruta: bloqueo temporal tras intentos fallidos ──
define('LOGIN_MAX_INTENTOS', 5);
define('LOGIN_VENTANA_SEG', 900);   // ventana de conteo: 15 min
define('LOGIN_BLOQUEO_SEG', 60);    // bloqueo: 1 min

function loginIntentosFile(): string {
    $ip = getIp();
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 50);
    return sys_get_temp_dir() . '/login_intentos_' . md5($ip . '|' . $ua) . '.json';
}

function loginBloqueoActivo(): ?int {
    $file = loginIntentosFile();
    if (!file_exists($file)) return null;
    $datos = json_decode((string) file_get_contents($file), true) ?: [];
    $fallidos = $datos['fallidos'] ?? [];
    if (count($fallidos) >= LOGIN_MAX_INTENTOS) {
        $restante = LOGIN_BLOQUEO_SEG - (time() - end($fallidos));
        if ($restante > 0) return $restante;
    }
    return null;
}

function loginRegistrarFallo(): void {
    $file = loginIntentosFile();
    $datos = file_exists($file)
        ? (json_decode((string) file_get_contents($file), true) ?: [])
        : [];
    $ahora = time();
    $fallidos = array_values(array_filter(
        $datos['fallidos'] ?? [],
        function ($t) use ($ahora) { return $ahora - (int) $t <= LOGIN_VENTANA_SEG; }
    ));
    $fallidos[] = $ahora;
    file_put_contents($file, json_encode(['fallidos' => $fallidos]), LOCK_EX);
}

function loginLimpiarIntentos(): void {
    @unlink(loginIntentosFile());
}

if (isPost()) {
    if (($restante = loginBloqueoActivo()) !== null) {
        $minutos = max(1, (int) ceil($restante / 60));
        $error = 'Demasiados intentos fallidos. Espere ' . $minutos .
                 ' minuto' . ($minutos > 1 ? 's' : '') . ' antes de volver a intentar.';
    } elseif (!verifyCSRF()) {
        $error = 'Token de seguridad inválido. Intente de nuevo.';
    } else {
        $username = getPost('username');
        $password = getPost('password');

        if (empty($username) || empty($password)) {
            $error = 'Ingrese usuario y contraseña.';
        } else {
            $user = Auth::login($username, $password);
            if ($user) {
                loginLimpiarIntentos();
                redirect(SITE_URL . '/index.php');
            } else {
                loginRegistrarFallo();
                $error = 'Usuario o contraseña incorrectos.';
                Audit::registrar(null, 'login_fallido', 'usuarios', null,
                    "Intento fallido de login para el usuario: {$username}");
            }
        }
    }
}

require_once PATH_VIEWS . '/layouts/login.php';
