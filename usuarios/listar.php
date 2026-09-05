<?php
/**
 * ARCHIVO: usuarios/listar.php
 * GESTION DE USUARIOS
 */
require_once dirname(__DIR__) . '/config/config.php';
Auth::requireAdmin();

$usuarios = Usuario::findAll();
$roles = Rol::findAll();

$errors = [];
$showForm = false;
$editUser = null;

if (isPost()) {
    if (!verifyCSRF()) {
        $errors[] = 'Token de seguridad inválido.';
    } else {
        $action = getPost('action');

        if ($action === 'crear') {
            $data = [
                'rol_id'    => getPost('rol_id'),
                'nombres'   => getPost('nombres'),
                'apellidos' => getPost('apellidos'),
                'dni'       => getPost('dni'),
                'email'     => getPost('email'),
                'telefono'  => getPost('telefono'),
                'username'  => getPost('username'),
                'password'  => getPost('password'),
                'estado'    => (int) getPost('estado', 1)
            ];

            if (empty($data['nombres'])) $errors[] = 'Los nombres son obligatorios.';
            if (empty($data['apellidos'])) $errors[] = 'Los apellidos son obligatorios.';
            if (empty($data['username'])) $errors[] = 'El usuario es obligatorio.';
            if (empty($data['password'])) $errors[] = 'La contraseña es obligatoria.';
            if (strlen($data['password']) < 6) $errors[] = 'La contraseña debe tener al menos 6 caracteres.';
            if (!empty($data['dni']) && strlen($data['dni']) > 8) $errors[] = 'El DNI debe tener como máximo 8 caracteres.';
            if (!empty($data['telefono']) && strlen($data['telefono']) > 9) $errors[] = 'El teléfono debe tener como máximo 9 caracteres.';
            if (!empty($data['dni']) && !ctype_digit($data['dni'])) $errors[] = 'El DNI debe contener solo números.';
            if (!empty($data['telefono']) && !ctype_digit($data['telefono'])) $errors[] = 'El teléfono debe contener solo números.';
            if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'El email no es válido.';
            if (!validarUsername($data['username'])) $errors[] = 'El usuario solo puede contener letras, números, punto, guion o guion bajo (3-50 caracteres).';
            if (!esUnoDe($data['estado'], [0, 1])) $errors[] = 'El estado no es válido.';
            if (!esEnteroOpcional($data['rol_id'], 1, 9999) || !existeRegistro('roles', 'id', (int) $data['rol_id'])) {
                $errors[] = 'El rol seleccionado no es válido.';
            }

            if (!empty($data['username']) && Usuario::existsUsername($data['username'])) {
                $errors[] = 'El nombre de usuario ya existe.';
            }

            if (empty($errors)) {
                $userId = Usuario::create($data);
                if ($userId) {
                    Audit::registrar(Auth::id(), 'usuario_creacion', 'usuarios', $userId,
                        "Usuario creado: {$data['username']}");
                    flash('success', 'Usuario creado correctamente.');
                    redirect(SITE_URL . '/usuarios/listar.php');
                } else {
                    $errors[] = 'Error al crear el usuario.';
                }
            }
        } elseif ($action === 'editar') {
            $userId = getPostInt('user_id');
            $target = Usuario::findById($userId);
            if (!$target) {
                $errors[] = 'El usuario a editar no existe.';
            }
            $data = [
                'rol_id'    => getPost('rol_id'),
                'nombres'   => getPost('nombres'),
                'apellidos' => getPost('apellidos'),
                'dni'       => getPost('dni'),
                'email'     => getPost('email'),
                'telefono'  => getPost('telefono'),
                'username'  => getPost('username'),
                'estado'    => (int) getPost('estado', 1)
            ];
            $password = getPost('password');
            if (!empty($password)) {
                $data['password'] = $password;
                if (strlen($password) < 6) $errors[] = 'La contraseña debe tener al menos 6 caracteres.';
            }

            if (empty($data['nombres'])) $errors[] = 'Los nombres son obligatorios.';
            if (empty($data['username'])) $errors[] = 'El usuario es obligatorio.';
            if (!validarUsername($data['username'])) $errors[] = 'El usuario solo puede contener letras, números, punto, guion o guion bajo (3-50 caracteres).';
            if (!empty($data['dni']) && strlen($data['dni']) > 8) $errors[] = 'El DNI debe tener como máximo 8 caracteres.';
            if (!empty($data['telefono']) && strlen($data['telefono']) > 9) $errors[] = 'El teléfono debe tener como máximo 9 caracteres.';
            if (!empty($data['dni']) && !ctype_digit($data['dni'])) $errors[] = 'El DNI debe contener solo números.';
            if (!empty($data['telefono']) && !ctype_digit($data['telefono'])) $errors[] = 'El teléfono debe contener solo números.';
            if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'El email no es válido.';
            if (!esUnoDe($data['estado'], [0, 1])) $errors[] = 'El estado no es válido.';
            if (!esEnteroOpcional($data['rol_id'], 1, 9999) || !existeRegistro('roles', 'id', (int) $data['rol_id'])) {
                $errors[] = 'El rol seleccionado no es válido.';
            }

            // Impedir que un admin se desactive a si mismo o cambie su propio rol (evita encierro).
            if ($target && $userId === Auth::id()) {
                if ($data['estado'] === 0) {
                    $errors[] = 'No puede desactivar su propia cuenta.';
                }
                if ((int) $data['rol_id'] !== (int) $target['rol_id'] && $target['rol_id'] == 1) {
                    $errors[] = 'No puede cambiar su propio rol de administrador.';
                }
            }

            if (!empty($data['username']) && Usuario::existsUsername($data['username'], $userId)) {
                $errors[] = 'El nombre de usuario ya existe.';
            }

            if (empty($errors)) {
                Usuario::update($userId, $data);
                Audit::registrar(Auth::id(), 'usuario_modificacion', 'usuarios', $userId,
                    "Usuario modificado: {$data['username']}");
                flash('success', 'Usuario actualizado correctamente.');
                redirect(SITE_URL . '/usuarios/listar.php');
            }
        }
    }
}

if (isset($_GET['edit'])) {
    $editUser = Usuario::findById((int)$_GET['edit']);
    $showForm = true;
}

$pageTitle = 'Gestión de Usuarios';
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

<div class="card">
    <div class="card-header">
        <h3>Usuarios del Sistema</h3>
        <a href="?new=1" class="btn btn-sm btn-primary">+ Nuevo Usuario</a>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Nombres</th>
                        <th>Apellidos</th>
                        <th>DNI</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Último Acceso</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td><strong><?= sanitize($u['username']) ?></strong></td>
                        <td><?= sanitize($u['nombres']) ?></td>
                        <td><?= sanitize($u['apellidos']) ?></td>
                        <td><?= sanitize($u['dni'] ?? '-') ?></td>
                        <td><span class="badge badge-info"><?= sanitize($u['rol_nombre']) ?></span></td>
                        <td>
                            <span class="badge <?= $u['estado'] ? 'badge-success' : 'badge-danger' ?>">
                                <?= $u['estado'] ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td><?= $u['ultimo_acceso'] ? dateTimeFormat($u['ultimo_acceso']) : 'Nunca' ?></td>
                        <td>
                            <a href="?edit=<?= $u['id'] ?>" class="btn btn-sm btn-outline">Editar</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($showForm || isset($_GET['new'])): ?>
<div class="card" style="margin-top:16px;">
    <div class="card-header">
        <h3><?= $editUser ? 'Editar Usuario' : 'Nuevo Usuario' ?></h3>
        <a href="<?= SITE_URL ?>/usuarios/listar.php" class="btn btn-sm btn-outline">Cancelar</a>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="<?= $editUser ? 'editar' : 'crear' ?>">
            <?php if ($editUser): ?>
            <input type="hidden" name="user_id" value="<?= $editUser['id'] ?>">
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label>Nombres <span class="required">*</span></label>
                    <input type="text" name="nombres" class="form-control" required
                           value="<?= sanitize($editUser['nombres'] ?? getPost('nombres')) ?>">
                </div>
                <div class="form-group">
                    <label>Apellidos <span class="required">*</span></label>
                    <input type="text" name="apellidos" class="form-control" required
                           value="<?= sanitize($editUser['apellidos'] ?? getPost('apellidos')) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>DNI</label>
                    <input type="text" name="dni" class="form-control" maxlength="8" data-numeric
                           value="<?= sanitize($editUser['dni'] ?? getPost('dni')) ?>">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= sanitize($editUser['email'] ?? getPost('email')) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" class="form-control" maxlength="9" data-numeric
                           value="<?= sanitize($editUser['telefono'] ?? getPost('telefono')) ?>">
                </div>
                <div class="form-group">
                    <label>Rol</label>
                    <select name="rol_id" class="form-control">
                        <?php foreach ($roles as $rol): ?>
                        <option value="<?= $rol['id'] ?>" <?= (($editUser['rol_id'] ?? getPost('rol_id')) == $rol['id']) ? 'selected' : '' ?>>
                            <?= sanitize($rol['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Usuario <span class="required">*</span></label>
                    <input type="text" name="username" class="form-control" required
                           value="<?= sanitize($editUser['username'] ?? getPost('username')) ?>">
                </div>
                <div class="form-group">
                    <label>Contraseña <?= $editUser ? '(dejar vacío para no cambiar)' : '<span class="required">*</span>' ?></label>
                    <input type="password" name="password" class="form-control" <?= $editUser ? '' : 'required' ?>>
                </div>
            </div>

            <div class="form-group">
                <label>Estado</label>
                <select name="estado" class="form-control" style="max-width:200px;">
                    <option value="1" <?= ($editUser['estado'] ?? 1) ? 'selected' : '' ?>>Activo</option>
                    <option value="0" <?= isset($editUser['estado']) && !$editUser['estado'] ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?= $editUser ? 'Guardar Cambios' : 'Crear Usuario' ?>
                </button>
                <a href="<?= SITE_URL ?>/usuarios/listar.php" class="btn btn-outline">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once PATH_VIEWS . '/layouts/main.php';
