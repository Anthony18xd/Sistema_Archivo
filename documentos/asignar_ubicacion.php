<?php
/**
 * ARCHIVO: documentos/asignar_ubicacion.php
 * ACCION: Asigna o quita la ubicacion fisica (caja) de un tomo.
 * Solo usuarios con permiso de escritura.
 */
require_once dirname(__DIR__) . '/config/config.php';
Auth::requireLogin();

if (!Auth::canWrite()) {
    flash('danger', 'No tienes permisos para asignar ubicaciones.');
    redirect(SITE_URL . '/documentos/buscar.php');
}

if (!isPost()) {
    redirect(SITE_URL . '/documentos/buscar.php');
}

if (!verifyCSRF()) {
    flash('danger', 'Token de seguridad inválido.');
    redirect(SITE_URL . '/documentos/buscar.php');
}

$idTomo = getPostInt('id_tomo');
$cajaId = getPostInt('caja_id');

$tomo = Tomo::findById($idTomo);
if (!$tomo) {
    flash('danger', 'El tomo no existe.');
    redirect(SITE_URL . '/documentos/buscar.php');
}

if ($cajaId > 0) {
    if (!existeRegistro('cajas', 'id', $cajaId)) {
        flash('danger', 'La caja seleccionada no es válida.');
        redirect(SITE_URL . '/documentos/ver_tomo.php?id=' . $idTomo);
    }
    $cajaId = $cajaId;
} else {
    $cajaId = null;
}

if (Tomo::asignarCaja($idTomo, $cajaId)) {
    if ($cajaId !== null) {
        Audit::registrar(Auth::id(), 'tomo_ubicacion', 'tomos', $idTomo,
            "Ubicacion asignada al tomo {$tomo['codigo_tomo']} (caja {$cajaId})");
        flash('success', 'Ubicación asignada al tomo.');
    } else {
        Audit::registrar(Auth::id(), 'tomo_ubicacion', 'tomos', $idTomo,
            "Ubicacion quitada al tomo {$tomo['codigo_tomo']}");
        flash('success', 'Ubicación quitada del tomo.');
    }
} else {
    flash('danger', 'No se pudo actualizar la ubicación del tomo.');
}

redirect(SITE_URL . '/documentos/ver_tomo.php?id=' . $idTomo);