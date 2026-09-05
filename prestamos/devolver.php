<?php
/**
 * ARCHIVO: prestamos/devolver.php
 * DEVOLVER DOCUMENTO PRESTADO
 */
require_once dirname(__DIR__) . '/config/config.php';
Auth::requireWrite();

$errors = [];
$prestamo = null;
$prestamoId = getQuery('prestamo_id');

if ($prestamoId) {
    $prestamo = Prestamo::findById((int)$prestamoId);
}

if (isPost()) {
    if (!verifyCSRF()) {
        $errors[] = 'Token de seguridad inválido.';
    } else {
        $pId = getPostInt('prestamo_id');
        $prestamo = Prestamo::findById($pId);

        if (!$prestamo) {
            $errors[] = 'Préstamo no encontrado.';
        } elseif ($prestamo['estado'] !== 'activo') {
            $errors[] = 'Este préstamo ya fue devuelto o no está activo.';
        }

        $data = [
            'fecha_devolucion_real'    => getPost('fecha_devolucion_real', date('Y-m-d')),
            'hora_devolucion_real'     => getPost('hora_devolucion_real', date('H:i')),
            'usuario_devolucion_id'    => Auth::id(),
            'estado_documento_entrada' => getPost('estado_documento_entrada'),
            'observaciones'            => getPost('observaciones')
        ];

        if (empty($errors)) {
            if (!esFechaValida($data['fecha_devolucion_real'])) {
                $errors[] = 'La fecha de devolución no es válida.';
            }
            if (!esHoraValida($data['hora_devolucion_real'])) {
                $errors[] = 'La hora de devolución no es válida.';
            }
            if ($prestamo && esFechaValida($data['fecha_devolucion_real']) &&
                strtotime($data['fecha_devolucion_real']) < strtotime($prestamo['fecha_salida'])) {
                $errors[] = 'La fecha de devolución no puede ser anterior a la fecha de salida.';
            }
            if (!empty($data['estado_documento_entrada']) && mb_strlen($data['estado_documento_entrada']) > 200) {
                $errors[] = 'El estado del documento es demasiado largo.';
            }
            if (!empty($data['observaciones']) && mb_strlen($data['observaciones']) > 500) {
                $errors[] = 'Las observaciones son demasiado largas.';
            }
        }

        if (empty($errors)) {
            $result = Prestamo::devolver($pId, $data);
            if ($result) {
                Documento::update($prestamo['documento_id'], [
                    'estado' => 'disponible',
                    'usuario_id' => Auth::id()
                ]);
                Historial::registrar($prestamo['documento_id'], Auth::id(), 'devolucion',
                    "Documento devuelto por {$data['usuario_devolucion_id']}. Estado: {$data['estado_documento_entrada']}");
                Audit::registrar(Auth::id(), 'prestamo_devolucion', 'prestamos', $pId,
                    "Devolución registrada para préstamo ID {$pId}");
                flash('success', 'Documento devuelto correctamente.');
                redirect(SITE_URL . '/prestamos/listar.php');
            } else {
                $errors[] = 'Error al registrar la devolución.';
            }
        }
    }
}

$pageTitle = 'Devolver Documento';
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
        <h3>Devolver Documento Prestado</h3>
    </div>
    <div class="card-body">
        <?php if (!$prestamo): ?>
        <div class="form-group">
            <label>Préstamo ID</label>
            <form method="GET" action="" style="display:flex; gap:10px;">
                <input type="number" name="prestamo_id" class="form-control" placeholder="Ingrese el ID del préstamo">
                <button type="submit" class="btn btn-primary">Buscar</button>
            </form>
        </div>
        <?php else: ?>
        <div style="background:#fffbeb; padding:14px; border-radius:var(--radius); border:1px solid #fde68a; margin-bottom:20px;">
            <strong>Documento:</strong> <?= sanitize($prestamo['documento_codigo']) ?><br>
            <strong>Asunto:</strong> <?= sanitize(mb_strimwidth($prestamo['documento_asunto'], 0, 80, '...')) ?><br>
            <strong>Solicitante:</strong> <?= sanitize($prestamo['solicitante_nombre']) ?><br>
            <strong>Fecha salida:</strong> <?= dateFormat($prestamo['fecha_salida']) ?> <?= timeFormat($prestamo['hora_salida']) ?><br>
            <strong>Devolución estimada:</strong> <?= dateFormat($prestamo['fecha_devolucion_estimada']) ?>
            <?php if (strtotime($prestamo['fecha_devolucion_estimada']) < time()): ?>
            <span class="badge badge-danger" style="margin-left:8px;">VENCIDO</span>
            <?php endif; ?>
        </div>

        <form method="POST" action="">
            <?= csrfField() ?>
            <input type="hidden" name="prestamo_id" value="<?= $prestamo['id'] ?>">

            <div class="form-row-3">
                <div class="form-group">
                    <label>Fecha Devolución</label>
                    <input type="date" name="fecha_devolucion_real" class="form-control"
                           value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label>Hora Devolución</label>
                    <input type="time" name="hora_devolucion_real" class="form-control"
                           value="<?= date('H:i') ?>">
                </div>
                <div class="form-group">
                    <label>Estado del documento al entrar</label>
                    <input type="text" name="estado_documento_entrada" class="form-control"
                           placeholder="Ej: Buen estado..."
                           value="<?= sanitize(getPost('estado_documento_entrada')) ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Observaciones</label>
                <input type="text" name="observaciones" class="form-control"
                       value="<?= sanitize(getPost('observaciones')) ?>">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-success">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="1 4 1 10 7 10"/>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                    </svg>
                    Registrar Devolución
                </button>
                <a href="<?= SITE_URL ?>/prestamos/listar.php" class="btn btn-outline">Cancelar</a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once PATH_VIEWS . '/layouts/main.php';
