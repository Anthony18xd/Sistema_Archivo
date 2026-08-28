<?php
/**
 * ARCHIVO: auth/logout.php
 * CIERRE DE SESION
 */
require_once dirname(__DIR__) . '/config/config.php';

Auth::logout();
flash('info', 'Sesion cerrada correctamente.');
redirect(SITE_URL . '/auth/login.php');
