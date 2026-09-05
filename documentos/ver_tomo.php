<?php
/**
 * ARCHIVO: documentos/ver_tomo.php
 * DETALLE DE TOMO - FASE 1
 * Muestra la informacion del tomo y sus documentos asociados
 */
require_once dirname(__DIR__) . '/config/config.php';
Auth::requireLogin();

PrestamoFase1::actualizarEstadoVencidos();

$id = getQueryInt('id');
if ($id <= 0) {
    flash('danger', 'Tomo no válido.');
    redirect(SITE_URL . '/documentos/buscar.php');
}

$tomo = Tomo::findById($id);
if (!$tomo) {
    flash('danger', 'El tomo no existe.');
    redirect(SITE_URL . '/documentos/buscar.php');
}

$documentos = DocumentoFase1::findByTomo($id);
$estaPrestado = PrestamoFase1::estaTomoPrestado($id);
$historial = PrestamoFase1::historialTomo($id);
$stats = DocumentoFase1::estadisticasPorTomo($id);

$pageTitle = 'Detalle de Tomo';
ob_start();
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:8px;">
    <a href="<?= SITE_URL ?>/documentos/buscar.php" class="btn btn-outline">
        &larr; Volver a Búsqueda
    </a>
    <div style="display:flex; gap:8px;">
        <?php if (Auth::canWrite()): ?>
            <?php if ($estaPrestado): ?>
                <a href="<?= SITE_URL ?>/documentos/buscar.php" class="btn btn-success">Devolver Tomo</a>
            <?php else: ?>
                <a href="<?= SITE_URL ?>/documentos/buscar.php" class="btn btn-warning">Registrar Préstamo</a>
            <?php endif; ?>
        <?php endif; ?>
        <?php if (Auth::isAdmin()): ?>
            <button type="button" class="btn btn-danger" onclick="confirmarEliminar(<?= $id ?>, '<?= sanitize(addslashes($tomo['codigo_tomo'])) ?>')">
                Eliminar Tomo
            </button>
        <?php endif; ?>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <h3>Información del Tomo</h3>
        </div>
        <div class="card-body">
            <table class="detalle-table">
                <tr>
                    <th>Código de Tomo</th>
                    <td><strong style="font-family:monospace;"><?= sanitize($tomo['codigo_tomo']) ?></strong></td>
                </tr>
                <tr>
                    <th>Año</th>
                    <td><?= $tomo['anio'] ? sanitize($tomo['anio']) : '-' ?></td>
                </tr>
                <tr>
                    <th>Área / Dependencia</th>
                    <td><?= sanitize($tomo['area'] ?? '-') ?></td>
                </tr>
                <tr>
                    <th>Tipo de Documento</th>
                    <td><?= sanitize($tomo['tipo_documento'] ?? '-') ?></td>
                </tr>
                <tr>
                    <th>Cantidad de Folios</th>
                    <td><?= $tomo['cantidad_folios'] ? formatNumber((int)$tomo['cantidad_folios']) : '-' ?></td>
                </tr>
                <tr>
                    <th>Ubicación Física</th>
                    <td>
                        <?php if (!empty($tomo['caja_id'])): ?>
                            <span class="badge badge-success">
                                <?= sanitize(implode(' / ', array_filter([
                                    $tomo['ambiente_nombre'] ?? null,
                                    $tomo['estante_codigo'] ?? null,
                                    ($tomo['nivel_numero'] ? 'Nivel ' . $tomo['nivel_numero'] : null),
                                    ($tomo['caja_numero'] ? 'Caja ' . $tomo['caja_numero'] : null),
                                ]))) ?>
                            </span>
                        <?php elseif ($tomo['ubicacion_estado'] === 'pendiente_asignacion'): ?>
                            <span class="badge badge-info">Pendiente de Asignación / Archivo General</span>
                        <?php else: ?>
                            <span class="badge badge-default"><?= ucfirst(str_replace('_', ' ', $tomo['ubicacion_estado'])) ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Estado</th>
                    <td>
                        <?php if ($estaPrestado): ?>
                            <span class="badge badge-warning">Prestado</span>
                        <?php else: ?>
                            <span class="badge badge-success">Disponible</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Origen</th>
                    <td>
                        <?php if (!empty($tomo['fuente_importacion']) && $tomo['fuente_importacion'] !== 'registro_manual'): ?>
                            <span class="badge badge-default">Importado: <?= sanitize($tomo['fuente_importacion']) ?></span>
                        <?php else: ?>
                            <span class="badge badge-default">Registro manual</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <?php if (Auth::canWrite()): ?>
            <form method="POST" action="<?= SITE_URL ?>/documentos/asignar_ubicacion.php"
                  style="margin-top:16px; padding-top:16px; border-top:1px solid var(--border-light);">
                <?= csrfField() ?>
                <input type="hidden" name="id_tomo" value="<?= (int) $tomo['id_tomo'] ?>">
                <label style="display:block; font-size:12px; text-transform:uppercase; color:var(--text-muted); margin-bottom:6px;">
                    Ubicación física (caja)
                </label>
                <?php $cajasUbicacion = Ubicacion::todasLasCajas(); ?>
                <?php if (empty($cajasUbicacion)): ?>
                    <div class="alert alert-warning" style="margin:0 0 8px;">
                        No hay cajas registradas. Crea ambientes, estantes, niveles y cajas en
                        <a href="<?= SITE_URL ?>/ubicaciones/administrar.php">Administrar Ubicaciones</a>.
                    </div>
                <?php else: ?>
                    <div class="form-row">
                        <div class="form-group" style="margin:0;">
                            <select name="caja_id" class="form-control">
                                <option value="">— Sin ubicación (Archivo General) —</option>
                                <?php foreach ($cajasUbicacion as $cu): ?>
                                <option value="<?= $cu['id'] ?>" <?= ($tomo['caja_id'] == $cu['id']) ? 'selected' : '' ?>>
                                    <?= sanitize($cu['ambiente_nombre'] . ' / ' . $cu['estante_codigo'] . ' / N' . $cu['nivel_numero'] . ' / Caja ' . $cu['numero']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin:0;">
                            <button type="submit" class="btn btn-primary btn-sm btn-block">Guardar Ubicación</button>
                        </div>
                    </div>
                <?php endif; ?>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Estadísticas</h3>
        </div>
        <div class="card-body">
            <div class="stats-grid" style="grid-template-columns:1fr 1fr; gap:12px; margin:0;">
                <div class="stat-card">
                    <div class="stat-info">
                        <h4>Documentos</h4>
                        <span class="stat-value"><?= formatNumber($stats['total']) ?></span>
                    </div>
                </div>
                <div class="stat-card stat-success">
                    <div class="stat-info">
                        <h4>Expedientes Únicos</h4>
                        <span class="stat-value"><?= formatNumber($stats['expedientes_unicos']) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card" style="margin-top:16px;">
    <div class="card-header">
        <h3>Documentos del Tomo (<?= formatNumber($stats['total']) ?>)</h3>
    </div>
    <div class="card-body" style="padding:0;">
        <?php if (empty($documentos)): ?>
            <div class="empty-state">
                <p>Este tomo no tiene documentos asociados.</p>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table-main">
                <thead>
                    <tr>
                        <th>Solicitante</th>
                        <th>Folios</th>
                        <th>Expediente(s)</th>
                        <th>Asunto</th>
                        <th>Origen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documentos as $doc): ?>
                    <tr>
                        <td><?= sanitize($doc['solicitante'] ?? '-') ?></td>
                        <td>
                            <?php if (!empty($doc['folios_texto'])): ?>
                                <?php
                                $claseF = 'folio-badge';
                                if (stripos($doc['folios_texto'], 'INDETERMINADO') !== false) $claseF .= ' folio-indeterminado';
                                if (stripos($doc['folios_texto'], 'SIN FOLIAR') !== false) $claseF .= ' folio-sin-foliar';
                                ?>
                                <span class="<?= $claseF ?>"><?= sanitize($doc['folios_texto']) ?></span>
                            <?php else: ?>-<?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($doc['expedientes'])): ?>
                                <div class="expedientes-list">
                                    <?php foreach (explode('|', $doc['expedientes']) as $exp): ?>
                                        <span class="exp-badge"><?= sanitize($exp) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>-<?php endif; ?>
                        </td>
                        <td><?= sanitize(mb_strimwidth($doc['asunto'] ?? '-', 0, 80, '...')) ?></td>
                        <td>
                            <?php if (!empty($doc['hoja_origen']) || !empty($doc['fila_origen'])): ?>
                                <small style="color:var(--text-muted);">
                                    Hoja: <?= sanitize($doc['hoja_origen'] ?? '-') ?>
                                    · Fila: <?= sanitize($doc['fila_origen'] ?? '-') ?>
                                </small>
                            <?php else: ?>
                                <small style="color:var(--text-muted);">Manual</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($historial)): ?>
<div class="card" style="margin-top:16px;">
    <div class="card-header">
        <h3>Historial de Préstamos</h3>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table class="table-main">
                <thead>
                    <tr>
                        <th>Solicitante</th>
                        <th>Área Destino</th>
                        <th>Salida</th>
                        <th>Estado</th>
                        <th>Devolución</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historial as $p): ?>
                    <tr>
                        <td><?= sanitize($p['solicitante_prestamo']) ?></td>
                        <td><?= sanitize($p['area_destino'] ?? '-') ?></td>
                        <td><?= dateFormat($p['fecha_salida']) ?></td>
                        <td>
                            <?php
                            $clase = 'badge-default';
                            if ($p['estado'] === 'activo') $clase = 'badge-warning';
                            elseif ($p['estado'] === 'devuelto') $clase = 'badge-success';
                            elseif ($p['estado'] === 'vencido') $clase = 'badge-danger';
                            ?>
                            <span class="badge <?= $clase ?>"><?= ucfirst($p['estado']) ?></span>
                        </td>
                        <td><?= $p['fecha_devolucion'] ? dateFormat($p['fecha_devolucion']) : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.detalle-table { width:100%; border-collapse:collapse; }
.detalle-table th {
    text-align:left; font-size:12px; text-transform:uppercase;
    color:var(--text-light); padding:8px 12px 8px 0;
    width:40%; font-weight:600;
    border-bottom:1px solid var(--border-light);
}
.detalle-table td {
    padding:8px 0; border-bottom:1px solid var(--border-light);
    vertical-align:middle;
}
.detalle-table tr:last-child th, .detalle-table tr:last-child td { border-bottom:none; }
.btn-warning { background: var(--warning); color:#fff; }
.btn-warning:hover { background: var(--accent-dark); color:#fff; }
</style>

<?php if (Auth::isAdmin()): ?>
<!-- MODAL: CONFIRMAR ELIMINACION -->
<div class="modal-overlay" id="modalEliminar">
    <div class="modal-box" style="max-width:420px;">
        <div class="modal-header">
            <h3>Eliminar Tomo</h3>
            <button class="modal-close" onclick="document.getElementById('modalEliminar').classList.remove('active')">&times;</button>
        </div>
        <form method="POST" action="<?= SITE_URL ?>/documentos/eliminar_tomo.php" id="formEliminar">
            <?= csrfField() ?>
            <input type="hidden" name="id_tomo" id="eliminar_id_tomo">
            <input type="hidden" name="volver" value="<?= sanitize(SITE_URL . '/documentos/ver_tomo.php?id=' . (int)($tomo['id_tomo'] ?? 0)) ?>">
            <div class="modal-body">
                <div class="alert alert-danger" style="margin:0 0 12px;">
                    <strong>Atención:</strong> Esta acción eliminará permanentemente el tomo y sus documentos.
                    No puede deshacerse.
                </div>
                <div style="background:var(--bg); padding:10px 14px; border-radius:var(--radius);">
                    <strong style="color:var(--primary);">Tomo:</strong>
                    <span id="eliminar_codigo_display" style="font-family:monospace;"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modalEliminar').classList.remove('active')">Cancelar</button>
                <button type="submit" class="btn btn-danger">Sí, Eliminar</button>
            </div>
        </form>
    </div>
</div>
<script>
function confirmarEliminar(idTomo, codigo) {
    document.getElementById('eliminar_id_tomo').value = idTomo;
    document.getElementById('eliminar_codigo_display').textContent = codigo;
    document.getElementById('modalEliminar').classList.add('active');
}
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once PATH_VIEWS . '/layouts/main.php';
