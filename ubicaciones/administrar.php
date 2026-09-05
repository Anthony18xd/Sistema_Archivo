<?php
/**
 * ARCHIVO: ubicaciones/administrar.php
 * ADMINISTRACION DE UBICACIONES (Solo Admin)
 */
require_once dirname(__DIR__) . '/config/config.php';
Auth::requireAdmin();

$errors = [];
$action = getQuery('action', 'list');

if (isPost()) {
    if (!verifyCSRF()) {
        $errors[] = 'Token de seguridad inválido.';
    } else {
        $actionType = getPost('action');

        if ($actionType === 'crear_ambiente') {
            $nombre = getPost('nombre');
            if (empty($nombre)) $errors[] = 'El nombre es obligatorio.';
            elseif (mb_strlen($nombre) > 100) $errors[] = 'El nombre del ambiente es demasiado largo.';

            if (empty($errors)) {
                Ubicacion::crearAmbiente(['nombre' => $nombre, 'descripcion' => getPost('descripcion')]);
                Audit::registrar(Auth::id(), 'ubicacion_creacion', 'ambientes', null, "Ambiente creado: {$nombre}");
                flash('success', 'Ambiente creado.');
                redirect(SITE_URL . '/ubicaciones/administrar.php?action=list');
            }

        } elseif ($actionType === 'crear_estante') {
            $data = ['ambiente_id' => getPostInt('ambiente_id'), 'codigo' => getPost('codigo'), 'nombre' => getPost('nombre'), 'descripcion' => getPost('descripcion')];
            if (empty($data['codigo']) || empty($data['nombre'])) $errors[] = 'Código y nombre son obligatorios.';
            if (mb_strlen($data['codigo']) > 20) $errors[] = 'El código del estante es demasiado largo.';
            if (mb_strlen($data['nombre']) > 100) $errors[] = 'El nombre del estante es demasiado largo.';
            if (!existeRegistro('ambientes', 'id', (int) $data['ambiente_id'])) {
                $errors[] = 'El ambiente seleccionado no es válido.';
            }
            if (empty($errors)) {
                Ubicacion::crearEstante($data);
                Audit::registrar(Auth::id(), 'ubicacion_creacion', 'estantes', null, "Estante creado: {$data['codigo']}");
                flash('success', 'Estante creado.');
                redirect(SITE_URL . '/ubicaciones/administrar.php?action=list');
            }

        } elseif ($actionType === 'crear_nivel') {
            $data = ['estante_id' => getPostInt('estante_id'), 'numero' => getPostInt('numero'), 'descripcion' => getPost('descripcion')];
            if (!existeRegistro('estantes', 'id', (int) $data['estante_id'])) {
                $errors[] = 'El estante seleccionado no es válido.';
            }
            if ($data['numero'] <= 0) $errors[] = 'El número de nivel debe ser positivo.';

            if (empty($errors)) {
                // Evitar duplicado del par (estante, numero)
                $stmt = db()->prepare("SELECT COUNT(*) FROM niveles WHERE estante_id = :estante_id AND numero = :numero AND estado = 1");
                $stmt->execute([':estante_id' => $data['estante_id'], ':numero' => $data['numero']]);
                if ((int) $stmt->fetchColumn() > 0) {
                    $errors[] = 'Ese número de nivel ya existe en el estante seleccionado.';
                }
            }

            if (empty($errors)) {
                Ubicacion::crearNivel($data);
                Audit::registrar(Auth::id(), 'ubicacion_creacion', 'niveles', null, "Nivel {$data['numero']} creado en estante {$data['estante_id']}");
                flash('success', 'Nivel creado.');
                redirect(SITE_URL . '/ubicaciones/administrar.php?action=list');
            }

        } elseif ($actionType === 'crear_caja') {
            $data = ['nivel_id' => getPostInt('nivel_id'), 'numero' => getPostInt('numero'), 'codigo' => getPost('codigo'), 'descripcion' => getPost('descripcion'), 'capacidad' => getPostInt('capacidad')];
            if (empty($data['numero'])) $errors[] = 'El número de caja es obligatorio.';
            elseif ($data['numero'] <= 0) $errors[] = 'El número de caja debe ser positivo.';
            if ($data['capacidad'] <= 0) $errors[] = 'La capacidad debe ser un número positivo.';
            if (!existeRegistro('niveles', 'id', (int) $data['nivel_id'])) {
                $errors[] = 'El nivel seleccionado no es válido.';
            }
            if (empty($errors)) {
                Ubicacion::crearCaja($data);
                Audit::registrar(Auth::id(), 'ubicacion_creacion', 'cajas', null, "Caja creada en nivel {$data['nivel_id']}");
                flash('success', 'Caja creada.');
                redirect(SITE_URL . '/ubicaciones/administrar.php?action=list');
            }
        }
    }
}

$ambientes = Ubicacion::ambientes();
$estantes = Ubicacion::estantes();

$pageTitle = 'Administrar Ubicaciones';
ob_start();
?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul style="margin:0 0 0 16px;">
        <?php foreach ($errors as $err): ?>
        <li><?= sanitize($err) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="grid-3">
    <div class="card">
        <div class="card-header">
            <h3>Ambientes</h3>
        </div>
        <div class="card-body" style="padding:0;">
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Nombre</th></tr></thead>
                    <tbody>
                    <?php foreach ($ambientes as $a): ?>
                    <tr><td><?= sanitize($a['nombre']) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="crear_ambiente">
                <div class="form-group">
                    <input type="text" name="nombre" class="form-control" placeholder="Nuevo ambiente..." required>
                </div>
                <button type="submit" class="btn btn-primary btn-sm btn-block">+ Agregar</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Estantes</h3>
        </div>
        <div class="card-body" style="padding:0;">
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Código</th><th>Nombre</th><th>Ambiente</th></tr></thead>
                    <tbody>
                    <?php foreach ($estantes as $e): ?>
                    <tr>
                        <td><strong><?= sanitize($e['codigo']) ?></strong></td>
                        <td><?= sanitize($e['nombre']) ?></td>
                        <td><?= sanitize($e['ambiente_nombre']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="crear_estante">
                <div class="form-group">
                    <select name="ambiente_id" class="form-control" required>
                        <option value="">Ambiente...</option>
                        <?php foreach ($ambientes as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= sanitize($a['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group"><input type="text" name="codigo" class="form-control" placeholder="Código" required></div>
                    <div class="form-group"><input type="text" name="nombre" class="form-control" placeholder="Nombre" required></div>
                </div>
                <button type="submit" class="btn btn-primary btn-sm btn-block">+ Agregar Estante</button>
            </form>
        </div>
        <div class="card-body" style="border-top:1px solid var(--border-light);">
            <form method="POST" action="">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="crear_nivel">
                <div class="form-group">
                    <select name="estante_id" class="form-control" required>
                        <option value="">Estante para el nivel...</option>
                        <?php foreach ($estantes as $e): ?>
                        <option value="<?= $e['id'] ?>"><?= sanitize($e['codigo']) ?> - <?= sanitize($e['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group"><input type="number" name="numero" class="form-control" placeholder="N. nivel" required min="1"></div>
                    <div class="form-group"><input type="text" name="descripcion" class="form-control" placeholder="Descripción"></div>
                </div>
                <button type="submit" class="btn btn-primary btn-sm btn-block">+ Agregar Nivel</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Crear Caja</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="crear_caja">
                <div class="form-group">
                    <label>Estante</label>
                    <select class="form-control" id="estanteSelect" onchange="loadNiveles(this.value)" required>
                        <option value="">Seleccionar...</option>
                        <?php foreach ($estantes as $e): ?>
                        <option value="<?= $e['id'] ?>"><?= sanitize($e['codigo']) ?> - <?= sanitize($e['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nivel</label>
                    <select name="nivel_id" id="nivelSelect" class="form-control" required>
                        <option value="">Seleccionar estante primero...</option>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Número</label><input type="number" name="numero" class="form-control" required min="1"></div>
                    <div class="form-group"><label>Capacidad</label><input type="number" name="capacidad" class="form-control" min="1" value="50"></div>
                </div>
                <button type="submit" class="btn btn-primary btn-sm btn-block">+ Crear Caja</button>
            </form>
        </div>
    </div>
</div>

<script>
function loadNiveles(estanteId) {
    var select = document.getElementById('nivelSelect');
    select.innerHTML = '<option value="">Cargando...</option>';
    if (!estanteId) { select.innerHTML = '<option value="">Seleccionar estante...</option>'; return; }

    var xhr = new XMLHttpRequest();
    xhr.open('GET', '<?= SITE_URL ?>/api/niveles.php?estante_id=' + estanteId);
    xhr.onload = function() {
        if (xhr.status === 200) {
            var niveles = JSON.parse(xhr.responseText);
            var html = '<option value="">Seleccionar nivel...</option>';
            for (var i = 0; i < niveles.length; i++) {
                html += '<option value="' + niveles[i].id + '">Nivel ' + niveles[i].numero + '</option>';
            }
            select.innerHTML = html;
        }
    };
    xhr.send();
}
</script>

<?php
$content = ob_get_clean();
require_once PATH_VIEWS . '/layouts/main.php';
