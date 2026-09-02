<?php
/**
 * ARCHIVO: prestamos/registrar_prestamo.php
 * HANDLER: Registro rapido de prestamo desde el buscador
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
$solicitante = trim(getPost('solicitante_prestamo'));
$areaDestino = trim(getPost('area_destino'));
$fechaSalida = getPost('fecha_salida');
$fechaDevolucion = getPost('fecha_devolucion');
$observaciones = trim(getPost('observaciones'));

if (empty($solicitante) || empty($fechaSalida) || empty($fechaDevolucion) || $idTomo <= 0) {
    flash('danger', 'Complete todos los campos obligatorios.');
    redirect(SITE_URL . '/documentos/buscar.php');
}

// Verificar que el tomo existe
$tomo = Tomo::findById($idTomo);
if (!$tomo) {
    flash('danger', 'El tomo especificado no existe.');
    redirect(SITE_URL . '/documentos/buscar.php');
}

// Verificar que no este ya prestado
if (PrestamoFase1::estaTomoPrestado($idTomo)) {
    flash('warning', 'Este tomo ya tiene un prestamo activo.');
    redirect(SITE_URL . '/documentos/buscar.php');
}

try {
    PrestamoFase1::create([
        'id_tomo'              => $idTomo,
        'solicitante_prestamo' => $solicitante,
        'area_destino'         => $areaDestino ?: null,
        'fecha_salida'         => $fechaSalida,
        'fecha_devolucion'     => $fechaDevolucion,
        'usuario_registro_id'  => Auth::id(),
        'observaciones'        => $observaciones ?: null
    ]);

    Audit::registrar(
        Auth::id(),
        'prestamo_registro',
        'tomos',
        $idTomo,
        "Prestamo registrado: {$solicitante} - Tomo: {$tomo['codigo_tomo']}"
    );

    flash('success', "Prestamo registrado exitosamente. Tomo: {$tomo['codigo_tomo']}");
} catch (Exception $e) {
    error_log('Error registrando prestamo: ' . $e->getMessage());
    flash('danger', 'Error al registrar el prestamo. Intente de nuevo.');
}

redirect(SITE_URL . '/documentos/buscar.php');
