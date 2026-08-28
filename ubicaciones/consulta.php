<?php
/**
 * ARCHIVO: ubicaciones/consulta.php
 * CONSULTA DE UBICACIONES
 */
require_once dirname(__DIR__) . '/config/config.php';
Auth::requireLogin();

$ambientes = Ubicacion::ambientes();
$estantes = Ubicacion::estantes();
$seleccionAmbiente = getQuery('ambiente_id');
$seleccionEstante = getQuery('estante_id');

$estantesFiltrados = $seleccionAmbiente ? Ubicacion::estantes((int)$seleccionAmbiente) : $estantes;
$niveles = $seleccionEstante ? Ubicacion::niveles((int)$seleccionEstante) : [];
$cajas = [];

if (!empty($niveles)) {
    foreach ($niveles as $nivel) {
        $nivelCajas = Ubicacion::cajas($nivel['id']);
        $cajas = array_merge($cajas, $nivelCajas);
    }
}

$pageTitle = 'Consulta de Ubicaciones';
ob_start();
?>

<div class="card">
    <div class="card-header">
        <h3>Ubicaciones Físicas del Archivo</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <div class="form-row-3">
                <div class="form-group">
                    <label>Ambiente</label>
                    <select name="ambiente_id" class="form-control" onchange="this.form.submit()">
                        <option value="">Todos los ambientes</option>
                        <?php foreach ($ambientes as $amb): ?>
                        <option value="<?= $amb['id'] ?>" <?= $seleccionAmbiente == $amb['id'] ? 'selected' : '' ?>>
                            <?= sanitize($amb['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Estante</label>
                    <select name="estante_id" class="form-control" onchange="this.form.submit()">
                        <option value="">Todos los estantes</option>
                        <?php foreach ($estantesFiltrados as $est): ?>
                        <option value="<?= $est['id'] ?>" <?= $seleccionEstante == $est['id'] ? 'selected' : '' ?>>
                            <?= sanitize($est['codigo']) ?> - <?= sanitize($est['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <a href="<?= SITE_URL ?>/ubicaciones/consulta.php" class="btn btn-outline btn-block">Limpiar Filtros</a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($cajas)): ?>
<div class="card" style="margin-top:16px;">
    <div class="card-header">
        <h3>Cajas (<?= count($cajas) ?> en total)</h3>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Ambiente</th>
                        <th>Estante</th>
                        <th>Nivel</th>
                        <th>Caja</th>
                        <th>Código</th>
                        <th>Capacidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cajas as $caja): ?>
                    <tr>
                        <td><?= sanitize($caja['ambiente_nombre']) ?></td>
                        <td><?= sanitize($caja['estante_codigo']) ?></td>
                        <td><?= sanitize($caja['nivel_numero']) ?></td>
                        <td><?= sanitize($caja['numero']) ?></td>
                        <td><?= sanitize($caja['codigo'] ?? '-') ?></td>
                        <td><?= $caja['capacidad'] ? $caja['capacidad'] . ' docs' : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php elseif ($seleccionEstante): ?>
<div class="card" style="margin-top:16px;">
    <div class="card-body">
        <div class="empty-state">
            <p>No hay cajas registradas para este estante.</p>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once PATH_VIEWS . '/layouts/main.php';
