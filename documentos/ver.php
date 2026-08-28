<?php
/**
 * ARCHIVO: documentos/ver.php
 * VER DETALLE DE DOCUMENTO
 */
require_once dirname(__DIR__) . '/config/config.php';
Auth::requireLogin();

$id = getQuery('id');
if (!$id) {
    flash('warning', 'Documento no especificado.');
    redirect(SITE_URL . '/documentos/buscar.php');
}

$documento = Documento::findById((int)$id);
if (!$documento) {
    flash('danger', 'Documento no encontrado.');
    redirect(SITE_URL . '/documentos/buscar.php');
}

$historial = Historial::findByDocumento((int)$id);
$prestamosHistorial = Prestamo::historialDocumento((int)$id);

$pageTitle = 'Documento: ' . $documento['codigo'];
ob_start();
?>

<div class="card">
    <div class="card-header">
        <h3>
            <?= sanitize($documento['codigo']) ?>
            <?php
            $badgeClass = 'badge-default';
            if ($documento['estado'] === 'disponible') $badgeClass = 'badge-success';
            elseif ($documento['estado'] === 'prestado') $badgeClass = 'badge-warning';
            elseif ($documento['estado'] === 'en_revision') $badgeClass = 'badge-info';
            elseif ($documento['estado'] === 'inactivo') $badgeClass = 'badge-danger';
            ?>
            <span class="badge <?= $badgeClass ?>" style="margin-left:10px;"><?= ucfirst($documento['estado']) ?></span>
        </h3>
        <div>
            <?php if (Auth::canWrite()): ?>
            <a href="<?= SITE_URL ?>/documentos/editar.php?id=<?= $documento['id'] ?>" class="btn btn-sm btn-outline">Editar</a>
            <?php endif; ?>
            <a href="<?= SITE_URL ?>/documentos/buscar.php" class="btn btn-sm btn-outline">Volver</a>
        </div>
    </div>
    <div class="card-body">
        <div class="grid-2">
            <div>
                <h4 style="font-size:12px; text-transform:uppercase; color:var(--text-light); margin-bottom:12px;">Información del Documento</h4>
                <table>
                    <tr><td style="width:140px; font-weight:600;">Código</td><td><?= sanitize($documento['codigo']) ?></td></tr>
                    <tr><td style="font-weight:600;">Año</td><td><?= sanitize($documento['anio']) ?></td></tr>
                    <tr><td style="font-weight:600;">Área Emisora</td><td><?= sanitize($documento['area_emisora_nombre'] ?? 'No asignada') ?></td></tr>
                    <tr><td style="font-weight:600;">Área Custodio</td><td><?= sanitize($documento['area_custodio_nombre'] ?? 'No asignada') ?></td></tr>
                    <tr><td style="font-weight:600;">Tipo</td><td><?= sanitize($documento['tipo_documento_nombre'] ?? 'No definido') ?></td></tr>
                    <tr><td style="font-weight:600;">Folios</td><td><?= $documento['num_folios'] ? formatNumber((int)$documento['num_folios']) : '-' ?></td></tr>
                    <tr><td style="font-weight:600;">Fecha Registro</td><td><?= dateFormat($documento['fecha_registro']) ?></td></tr>
                    <tr><td style="font-weight:600;">Registrado por</td><td><?= sanitize($documento['usuario_registro_nombre']) ?></td></tr>
                </table>
            </div>
            <div>
                <h4 style="font-size:12px; text-transform:uppercase; color:var(--text-light); margin-bottom:12px;">Ubicación Física</h4>
                <?php if ($documento['ambiente_nombre']): ?>
                <div style="background:#f8fafc; padding:16px; border-radius:var(--radius); border:1px solid var(--border-light);">
                    <div class="location-display" style="font-size:14px;">
                        <span class="loc-part"><?= sanitize($documento['ambiente_nombre']) ?></span>
                        <span class="loc-sep">/</span>
                        <span class="loc-part"><?= sanitize($documento['estante_nombre']) ?> (<?= sanitize($documento['estante_codigo']) ?>)</span>
                        <span class="loc-sep">/</span>
                        <span class="loc-part">Nivel <?= sanitize($documento['nivel_numero']) ?></span>
                        <span class="loc-sep">/</span>
                        <span class="loc-part">Caja N. <?= sanitize($documento['caja_numero']) ?></span>
                    </div>
                </div>
                <?php else: ?>
                <div style="background:#f8fafc; padding:16px; border-radius:var(--radius); border:1px solid var(--border-light); color:var(--text-muted);">
                    Documento sin ubicación física asignada.
                    <?php if (Auth::canWrite()): ?>
                    <br><a href="<?= SITE_URL ?>/documentos/editar.php?id=<?= $documento['id'] ?>">Asignar ubicación</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <h4 style="font-size:12px; text-transform:uppercase; color:var(--text-light); margin:16px 0 8px;">Asunto</h4>
                <p style="font-size:13px; line-height:1.6;"><?= nl2br(sanitize($documento['asunto'])) ?></p>

                <?php if ($documento['descripcion']): ?>
                <h4 style="font-size:12px; text-transform:uppercase; color:var(--text-light); margin:16px 0 8px;">Descripción</h4>
                <p style="font-size:13px; line-height:1.6;"><?= nl2br(sanitize($documento['descripcion'])) ?></p>
                <?php endif; ?>

                <?php if ($documento['observaciones']): ?>
                <h4 style="font-size:12px; text-transform:uppercase; color:var(--text-light); margin:16px 0 8px;">Observaciones</h4>
                <p style="font-size:13px; line-height:1.6; color:var(--text-light);"><?= nl2br(sanitize($documento['observaciones'])) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($prestamosHistorial)): ?>
<div class="card" style="margin-top:16px;">
    <div class="card-header">
        <h3>Historial de Préstamos</h3>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Solicitante</th>
                        <th>DNI</th>
                        <th>Área</th>
                        <th>Fecha Salida</th>
                        <th>Devolución Estimada</th>
                        <th>Devolución Real</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prestamosHistorial as $p): ?>
                    <tr>
                        <td><?= sanitize($p['solicitante_nombre']) ?></td>
                        <td><?= sanitize($p['solicitante_dni'] ?? '-') ?></td>
                        <td><?= sanitize($p['solicitante_area'] ?? '-') ?></td>
                        <td><?= dateFormat($p['fecha_salida']) ?> <?= timeFormat($p['hora_salida']) ?></td>
                        <td><?= dateFormat($p['fecha_devolucion_estimada']) ?></td>
                        <td><?= $p['fecha_devolucion_real'] ? dateFormat($p['fecha_devolucion_real']) . ' ' . timeFormat($p['hora_devolucion_real']) : '-' ?></td>
                        <td>
                            <?php
                            $badgeClass = 'badge-default';
                            if ($p['estado'] === 'activo') $badgeClass = 'badge-warning';
                            elseif ($p['estado'] === 'devuelto') $badgeClass = 'badge-success';
                            elseif ($p['estado'] === 'vencido') $badgeClass = 'badge-danger';
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= ucfirst($p['estado']) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($historial)): ?>
<div class="card" style="margin-top:16px;">
    <div class="card-header">
        <h3>Historial de Cambios</h3>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historial as $h): ?>
                    <tr>
                        <td><?= dateTimeFormat($h['created_at']) ?></td>
                        <td><?= sanitize($h['usuario_nombre']) ?></td>
                        <td><span class="badge badge-info"><?= ucfirst(str_replace('_', ' ', $h['accion'])) ?></span></td>
                        <td><?= sanitize($h['descripcion'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once PATH_VIEWS . '/layouts/main.php';
