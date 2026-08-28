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

if (isPost()) {
    if (!verifyCSRF()) {
        $error = 'Token de seguridad invalido. Intente de nuevo.';
    } else {
        $username = getPost('username');
        $password = getPost('password');

        if (empty($username) || empty($password)) {
            $error = 'Ingrese usuario y contraseña.';
        } else {
            $user = Auth::login($username, $password);
            if ($user) {
                redirect(SITE_URL . '/index.php');
            } else {
                $error = 'Usuario o contraseña incorrectos.';
                Audit::registrar(null, 'login_fallido', 'usuarios', null,
                    "Intento fallido de login para el usuario: {$username}");
            }
        }
    }
}

require_once PATH_VIEWS . '/layouts/login.php';
