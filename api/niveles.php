<?php
/**
 * ARCHIVO: api/niveles.php
 * API AJAX - Obtener niveles de un estante
 */
require_once dirname(__DIR__) . '/config/config.php';
header('Content-Type: application/json; charset=utf-8');

$estanteId = (int) ($_GET['estante_id'] ?? 0);
if ($estanteId <= 0) {
    jsonResponse([], 400);
}

$niveles = Ubicacion::niveles($estanteId);
$response = [];
foreach ($niveles as $n) {
    $response[] = ['id' => $n['id'], 'numero' => $n['numero'], 'descripcion' => $n['descripcion'] ?? ''];
}

jsonResponse($response);
