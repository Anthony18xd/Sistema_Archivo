<?php
/**
 * ARCHIVO: documentos/eliminar_tomo.php
 * HANDLER: Eliminacion logica de un tomo (SOLO ADMINISTRADOR)
 */
require_once dirname(__DIR__) . '/config/config.php';
Auth::requireLogin();

$volver = SITE_URL . '/documentos/listar.php';
if (isset($_POST['volver']) && str_starts_with($_POST['volver'], SITE_URL)) {
    $volver = $_POST['volver'];
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