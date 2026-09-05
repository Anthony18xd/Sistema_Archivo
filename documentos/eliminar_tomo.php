<?php
/**
 * ARCHIVO: documentos/eliminar_tomo.php
 * HANDLER: Eliminacion logica de un tomo (SOLO ADMINISTRADOR)
 */
require_once dirname(__DIR__) . '/config/config.php';
Auth::requireLogin();

$volver = SITE_URL . '/documentos/listar.php';
if (isset($_POST['volver'])) {
    $candidato = trim($_POST['volver']);
    // Evitar salto de cabecera (CRLF) y redireccion a sitios externos.
    if (preg_match('/[\r\n\0]/', $candidato)) {
        $candidato = '';
    }
    $path = parse_url($candidato, PHP_URL_PATH) ?: '';
    if ($path && str_starts_with($path, '/documentos/')) {
        $volver = $candidato;
    }
}

// Solo administradores
if (!Auth::isAdmin()) {
    flash('danger', 'No tiene permisos para eliminar tomos. Esta acción está restringida al administrador.');
    redirect($volver);
}

if (!isPost() || !verifyCSRF()) {
    flash('danger', 'Token de seguridad inválido.');
    redirect($volver);
}

$idTomo = getPostInt('id_tomo');
if ($idTomo <= 0) {
    flash('danger', 'Tomo no válido.');
    redirect($volver);
}

$resultado = Tomo::eliminar($idTomo);

if ($resultado['ok']) {
    flash('success', 'El tomo fue eliminado correctamente.');
} else {
    flash('danger', $resultado['error']);
}

redirect($volver);