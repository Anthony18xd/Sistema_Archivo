<?php
/**
 * ARCHIVO: auditoria/index.php
 * CONSULTA DE AUDITORIA
 */
require_once dirname(__DIR__) . '/config/config.php';
Auth::requireAdmin();

$filtros = [];
if (!empty($_GET['accion'])) $filtros['accion'] = getQuery('accion');
if (!empty($_GET['fecha_desde'])) $filtros['fecha_desde'] = getQuery('fecha_desde');
if (!empty($_GET['fecha_hasta'])) $filtros['fecha_hasta'] = getQuery('fecha_hasta');
if (!empty($_GET['usuario_id'])) $filtros['usuario_id'] = getQuery('usuario_id');

$total = Audit::contar($filtros);
$registros = Audit::getRegistros(100, 0, $filtros);
$usuarios = Usuario::findAll();

$pageTitle = 'Auditoria del Sistema';
ob_start();
?>

<div class="card">
    <div class="card-header">
        <h3>Registro de Auditoria</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <div class="form-row" style="grid-template-columns: 1fr 1fr 1fr 1fr auto;">
                <div class="form-group">
                    <label>Accion</label>
                    <select name="accion" class="form-control">
                        <option value="">Todas</option>
                        <option value="inicio_sesion" <?= getQuery('accion') === 'inicio_sesion' ? 'selected' : '' ?>>Inicio de sesion</option>
                        <option value="cierre_sesion" <?= getQuery('accion') === 'cierre_sesion' ? 'selected' : '' ?>>Cierre de sesion</option>
                        <option value="documento_registro" <?= getQuery('accion') === 'documento_registro' ? 'selected' : '' ?>>Registro documento</option>
                        <option value="documento_modificacion" <?= getQuery('accion') === 'documento_modificacion' ? 'selected' : '' ?>>Modificacion documento</option>
                        <option value="prestamo_registro" <?= getQuery('accion') === 'prestamo_registro' ? 'selected' : '' ?>>Registro prestamo</option>
                        <option value="prestamo_devolucion" <?= getQuery('accion') === 'prestamo_devolucion' ? 'selected' : '' ?>>Devolucion prestamo</option>
                        <option value="login_fallido" <?= getQuery('accion') === 'login_fallido' ? 'selected' : '' ?>>Login fallido</option>
                        <option value="usuario_creacion" <?= getQuery('accion') === 'usuario_creacion' ? 'selected' : '' ?>>Creacion usuario</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Fecha Desde</label>
                    <input type="date" name="fecha_desde" class="form-control"
                           value="<?= sanitize(getQuery('fecha_desde')) ?>">
                </div>
                <div class="form-group">
                    <label>Fecha Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control"
                           value="<?= sanitize(getQuery('fecha_hasta')) ?>">
                </div>
                <div class="form-group">
                    <label>Usuario</label>
                    <select name="usuario_id" class="form-control">
                        <option value="">Todos</option>
                        <?php foreach ($usuarios as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= getQuery('usuario_id') == $u['id'] ? 'selected' : '' ?>>
                            <?= sanitize($u['username']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card" style="margin-top:16px;">
    <div class="card-header">
        <h3>Registros (<?= formatNumber($total) ?>)</h3>
    </div>
    <div class="card-body" style="padding:0;">
        <?php if (empty($registros)): ?>
            <div class="empty-state">
                <p>No hay registros de auditoria.</p>
            </div>
        <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Fecha/Hora</th>
                        <th>Usuario</th>
                        <th>Accion</th>
                        <th>Tabla</th>
                        <th>ID Registro</th>
                        <th>Detalle</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registros as $reg): ?>
                    <tr>
                        <td style="white-space:nowrap;"><?= dateTimeFormat($reg['created_at']) ?></td>
                        <td><?= $reg['username'] ? sanitize($reg['username']) : '<em>Sistema</em>' ?></td>
                        <td><span class="badge badge-info"><?= sanitize(str_replace('_', ' ', $reg['accion'])) ?></span></td>
                        <td><?= sanitize($reg['tabla'] ?? '-') ?></td>
                        <td><?= $reg['registro_id'] ?? '-' ?></td>
                        <td title="<?= sanitize($reg['detalle'] ?? '') ?>"><?= sanitize(mb_strimwidth($reg['detalle'] ?? '', 0, 50, '...')) ?></td>
                        <td style="font-family:monospace; font-size:12px;"><?= sanitize($reg['ip_address'] ?? '-') ?></td>
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
