<?php
/**
 * ARCHIVO: prestamos/listar.php
 * LISTADO DE PRESTAMOS
 */
require_once dirname(__DIR__) . '/config/config.php';
Auth::requireLogin();

Prestamo::actualizarEstadoVencidos();

$filtroEstado = getQuery('estado', '');
$filtros = [];
if ($filtroEstado) $filtros['estado'] = $filtroEstado;

$prestamos = Prestamo::buscar($filtros, 50, 0);

$pageTitle = 'Historial de Préstamos';
ob_start();
?>

<div class="card">
    <div class="card-header">
        <h3>Préstamos</h3>
        <?php if (Auth::canWrite()): ?>
        <div>
            <a href="<?= SITE_URL ?>/prestamos/registrar.php" class="btn btn-sm btn-primary">+ Nuevo Préstamo</a>
            <a href="<?= SITE_URL ?>/prestamos/devolver.php" class="btn btn-sm btn-success">Devolver</a>
        </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="search-box">
            <a href="<?= SITE_URL ?>/prestamos/listar.php" class="btn btn-sm <?= empty($filtroEstado) ? 'btn-primary' : 'btn-outline' ?>">Todos</a>
            <a href="?estado=activo" class="btn btn-sm <?= $filtroEstado === 'activo' ? 'btn-warning' : 'btn-outline' ?>">Activos</a>
            <a href="?estado=vencido" class="btn btn-sm <?= $filtroEstado === 'vencido' ? 'btn-danger' : 'btn-outline' ?>">Vencidos</a>
            <a href="?estado=devuelto" class="btn btn-sm <?= $filtroEstado === 'devuelto' ? 'btn-success' : 'btn-outline' ?>">Devueltos</a>
        </div>
    </div>
    <div class="card-body" style="padding:0;">
        <?php if (empty($prestamos)): ?>
            <div class="empty-state">
                <p>No hay préstamos registrados.</p>
            </div>
        <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Documento</th>
                        <th>Solicitante</th>
                        <th>DNI</th>
                        <th>Área</th>
                        <th>Fecha Salida</th>
                        <th>Devolución Est.</th>
                        <th>Devolución Real</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prestamos as $p): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td>
                            <a href="<?= SITE_URL ?>/documentos/ver.php?id=<?= $p['documento_id'] ?>">
                                <strong><?= sanitize($p['documento_codigo']) ?></strong>
                            </a>
                        </td>
                        <td><?= sanitize($p['solicitante_nombre']) ?></td>
                        <td><?= sanitize($p['solicitante_dni'] ?? '-') ?></td>
                        <td><?= sanitize($p['solicitante_area'] ?? '-') ?></td>
                        <td><?= dateFormat($p['fecha_salida']) ?></td>
                        <td>
                            <?= dateFormat($p['fecha_devolucion_estimada']) ?>
                            <?php if ($p['estado'] === 'activo' && strtotime($p['fecha_devolucion_estimada']) < time()): ?>
                            <span class="badge badge-danger" style="margin-left:4px;">VENCIDO</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $p['fecha_devolucion_real'] ? dateFormat($p['fecha_devolucion_real']) : '-' ?></td>
                        <td>
                            <?php
                            $badgeClass = 'badge-default';
                            if ($p['estado'] === 'activo') $badgeClass = 'badge-warning';
                            elseif ($p['estado'] === 'devuelto') $badgeClass = 'badge-success';
                            elseif ($p['estado'] === 'vencido') $badgeClass = 'badge-danger';
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= ucfirst($p['estado']) ?></span>
                        </td>
                        <td>
                            <?php if ($p['estado'] === 'activo' && Auth::canWrite()): ?>
                            <a href="<?= SITE_URL ?>/prestamos/devolver.php?prestamo_id=<?= $p['id'] ?>" class="btn btn-sm btn-success">Devolver</a>
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

<?php
$content = ob_get_clean();
require_once PATH_VIEWS . '/layouts/main.php';
