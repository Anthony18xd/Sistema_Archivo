<?php
/**
 * ARCHIVO: prestamos/devolver_tomo.php
 * HANDLER: Devolucion rapida de tomo desde el buscador
 */
require_once dirname(__DIR__) . '/config/config.php';
Auth::requireLogin();

if (!Auth::canWrite()) {
    flash('danger', 'No tiene permisos para realizar esta accion.');
    redirect(SITE_URL . '/documentos/buscar.php');
}

if (!isPost() || !verifyCSRF()) {
    redirect(SITE_URL . '/documentos/buscar.php');
}

$idTomo = getPostInt('id_tomo');
$observaciones = trim(getPost('observaciones'));

if ($idTomo <= 0) {
    flash('danger', 'Tomo no valido.');
    redirect(SITE_URL . '/documentos/buscar.php');
}

// Buscar el prestamo activo de este tomo
$stmt = db()->prepare(
    "SELECT id_prestamo FROM prestamos_fase1
     WHERE id_tomo = :id_tomo AND estado = 'activo'
     LIMIT 1"
);
$stmt->execute([':id_tomo' => $idTomo]);
$prestamo = $stmt->fetch();

if (!$prestamo) {
    flash('warning', 'No se encontro un prestamo activo para este tomo.');
    redirect(SITE_URL . '/documentos/buscar.php');
}

try {
    PrestamoFase1::devolver((int) $prestamo['id_prestamo'], Auth::id());

    $tomo = Tomo::findById($idTomo);
    $codigo = $tomo ? $tomo['codigo_tomo'] : '#{$idTomo}';

    Audit::registrar(
        Auth::id(),
        'prestamo_devolucion',
        'tomos',
        $idTomo,
        "Devolucion registrada - Tomo: {$codigo}" .
        ($observaciones ? " - Obs: {$observaciones}" : '')
    );

    flash('success', "Devolucion registrada. Tomo: {$codigo}");
} catch (Exception $e) {
    error_log('Error devolviendo tomo: ' . $e->getMessage());
    flash('danger', 'Error al registrar la devolucion. Intente de nuevo.');
}

redirect(SITE_URL . '/documentos/buscar.php');
