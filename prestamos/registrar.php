<?php
/**
 * ARCHIVO: prestamos/registrar.php
 * REGISTRAR PRESTAMO DE DOCUMENTO
 */
require_once dirname(__DIR__) . '/config/config.php';
Auth::requireWrite();

$errors = [];
$documento = null;
$documentoId = getQuery('doc_id');

if ($documentoId) {
    $documento = Documento::findById((int)$documentoId);
}

$prestamosConfig = db()->prepare("SELECT valor FROM configuracion WHERE clave = 'dias_prestamo_default'");
$prestamosConfig->execute();
$diasDefault = (int) ($prestamosConfig->fetchColumn() ?: 15);

if (isPost()) {
    if (!verifyCSRF()) {
        $errors[] = 'Token de seguridad inválido.';
    } else {
        $docId = getPostInt('documento_id');
        $documento = Documento::findById($docId);

        if (!$documento) {
            $errors[] = 'Documento no encontrado.';
        } elseif ($documento['estado'] === 'prestado') {
            $errors[] = 'Este documento ya se encuentra prestado.';
        } elseif ($documento['estado'] !== 'disponible') {
            $errors[] = 'El documento no está disponible para préstamo.';
        }

        $data = [
            'documento_id'              => $docId,
            'solicitante_nombre'        => getPost('solicitante_nombre'),
            'solicitante_dni'           => getPost('solicitante_dni'),
            'solicitante_area'          => getPost('solicitante_area'),
            'motivo'                    => getPost('motivo'),
            'fecha_salida'             => getPost('fecha_salida', date('Y-m-d')),
            'hora_salida'              => getPost('hora_salida', date('H:i')),
            'fecha_devolucion_estimada'=> getPost('fecha_devolucion_estimada'),
            'usuario_prestamo_id'      => Auth::id(),
            'estado_documento_salida'  => getPost('estado_documento_salida'),
            'observaciones'            => getPost('observaciones')
        ];

        if (empty($data['solicitante_nombre'])) $errors[] = 'El nombre del solicitante es obligatorio.';
        if (empty($data['fecha_devolucion_estimada'])) $errors[] = 'La fecha estimada de devolución es obligatoria.';

        if (empty($errors)) {
            $prestamoId = Prestamo::create($data);
            if ($prestamoId) {
                Documento::update($docId, ['estado' => 'prestado', 'usuario_id' => Auth::id()]);
                Historial::registrar($docId, Auth::id(), 'prestamo',
                    "Documento prestado a {$data['solicitante_nombre']}. Devolucion estimada: {$data['fecha_devolucion_estimada']}");
                Audit::registrar(Auth::id(), 'prestamo_registro', 'prestamos', $prestamoId,
                    "Prestamo registrado para documento ID {$docId}");
                flash('success', 'Préstamo registrado correctamente.');
                redirect(SITE_URL . '/prestamos/listar.php');
            } else {
                $errors[] = 'Error al registrar el préstamo.';
            }
        }
    }
}

$pageTitle = 'Registrar Préstamo';
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
        <h3>Registrar Préstamo de Documento</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <?= csrfField() ?>

            <div class="form-group">
                <label>Documento <span class="required">*</span></label>
                <?php if ($documento): ?>
                <div style="background:#f0fdf4; padding:10px; border-radius:var(--radius); border:1px solid #bbf7d0;">
                    <strong><?= sanitize($documento['codigo']) ?></strong> -
                    <?= sanitize(mb_strimwidth($documento['asunto'], 0, 80, '...')) ?>
                    <span class="badge badge-success" style="margin-left:8px;"><?= ucfirst($documento['estado']) ?></span>
                </div>
                <input type="hidden" name="documento_id" value="<?= $documento['id'] ?>">
                <?php else: ?>
                <input type="number" name="documento_id" class="form-control" required
                       placeholder="Ingrese el ID del documento"
                       value="<?= getPost('documento_id') ?>">
                <small style="color:var(--text-muted);">Puede buscar el documento primero en la busqueda y venir con el ID.</small>
                <?php endif; ?>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Solicitante (Nombre completo) <span class="required">*</span></label>
                    <input type="text" name="solicitante_nombre" class="form-control" required
                           value="<?= sanitize(getPost('solicitante_nombre')) ?>">
                </div>
                <div class="form-group">
                    <label>DNI</label>
                    <input type="text" name="solicitante_dni" class="form-control"
                           value="<?= sanitize(getPost('solicitante_dni')) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Área Solicitante</label>
                    <input type="text" name="solicitante_area" class="form-control"
                           value="<?= sanitize(getPost('solicitante_area')) ?>">
                </div>
                <div class="form-group">
                    <label>Motivo</label>
                    <input type="text" name="motivo" class="form-control"
                           value="<?= sanitize(getPost('motivo')) ?>">
                </div>
            </div>

            <div class="form-row-3">
                <div class="form-group">
                    <label>Fecha Salida</label>
                    <input type="date" name="fecha_salida" class="form-control"
                           value="<?= sanitize(getPost('fecha_salida', date('Y-m-d'))) ?>">
                </div>
                <div class="form-group">
                    <label>Hora Salida</label>
                    <input type="time" name="hora_salida" class="form-control"
                           value="<?= sanitize(getPost('hora_salida', date('H:i'))) ?>">
                </div>
                <div class="form-group">
                    <label>Devolución Estimada <span class="required">*</span></label>
                    <input type="date" name="fecha_devolucion_estimada" class="form-control" required
                           value="<?= sanitize(getPost('fecha_devolucion_estimada', date('Y-m-d', strtotime("+{$diasDefault} days")))) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Estado del documento al salir</label>
                    <input type="text" name="estado_documento_salida" class="form-control"
                           placeholder="Ej: Buen estado, Con manchas..."
                           value="<?= sanitize(getPost('estado_documento_salida')) ?>">
                </div>
                <div class="form-group">
                    <label>Observaciones</label>
                    <input type="text" name="observaciones" class="form-control"
                           value="<?= sanitize(getPost('observaciones')) ?>">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 11 12 14 22 4"/>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                    </svg>
                    Registrar Préstamo
                </button>
                <a href="<?= SITE_URL ?>/index.php" class="btn btn-outline">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once PATH_VIEWS . '/layouts/main.php';
