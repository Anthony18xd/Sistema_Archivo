<?php
/**
 * ARCHIVO: documentos/registrar.php
 * REGISTRO DE NUEVO DOCUMENTO
 */
require_once dirname(__DIR__) . '/config/config.php';
Auth::requireWrite();

$errors = [];
$success = '';

$areas = Area::findAll();
$tipos = TipoDocumento::findAll();
$cajas = Ubicacion::todasLasCajas();

if (isPost()) {
    if (!verifyCSRF()) {
        $errors[] = 'Token de seguridad inválido.';
    } else {
        $data = [
            'codigo'            => getPost('codigo'),
            'anio'              => getPost('anio'),
            'area_emisora_id'   => getPost('area_emisora_id'),
            'area_custodio_id'  => getPost('area_custodio_id'),
            'tipo_documento_id' => getPost('tipo_documento_id'),
            'caja_id'           => getPost('caja_id'),
            'num_folios'        => getPost('num_folios'),
            'asunto'            => getPost('asunto'),
            'descripcion'       => getPost('descripcion'),
            'observaciones'     => getPost('observaciones'),
            'estado'            => getPost('estado', 'disponible'),
            'fecha_registro'    => getPost('fecha_registro', date('Y-m-d')),
            'usuario_registro_id' => Auth::id()
        ];

        if (empty($data['codigo'])) $errors[] = 'El código es obligatorio.';
        if (empty($data['asunto'])) $errors[] = 'El asunto es obligatorio.';
        if (empty($data['anio'])) $errors[] = 'El año es obligatorio.';

        if (!empty($data['codigo']) && Documento::existeCodigo($data['codigo'])) {
            $errors[] = 'El código ya está registrado. Use un código único.';
        }

        if (empty($errors)) {
            $docId = Documento::create($data);
            if ($docId) {
                Historial::registrar($docId, Auth::id(), 'registro', 'Documento registrado en el sistema');
                Audit::registrar(Auth::id(), 'documento_registro', 'documentos', $docId,
                    "Documento registrado: {$data['codigo']}");
                flash('success', "Documento {$data['codigo']} registrado correctamente.");
                redirect(SITE_URL . '/documentos/ver.php?id=' . $docId);
            } else {
                $errors[] = 'Error al registrar el documento.';
            }
        }
    }
}

$pageTitle = 'Registrar Documento';
ob_start();
?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <strong>Errores:</strong>
    <ul style="margin:4px 0 0 16px;">
        <?php foreach ($errors as $err): ?>
        <li><?= sanitize($err) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3>Registro de Nuevo Documento</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <?= csrfField() ?>

            <div class="form-row">
                <div class="form-group">
                    <label>Código <span class="required">*</span></label>
                    <input type="text" name="codigo" class="form-control" required
                           placeholder="Ej: TOMO-001-2026"
                           value="<?= sanitize(getPost('codigo')) ?>">
                </div>
                <div class="form-group">
                    <label>Año <span class="required">*</span></label>
                    <input type="number" name="anio" class="form-control" required
                           min="1900" max="<?= date('Y') ?>"
                           value="<?= sanitize(getPost('anio', date('Y'))) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Área Emisora</label>
                    <select name="area_emisora_id" class="form-control">
                        <option value="">Seleccionar...</option>
                        <?php foreach ($areas as $area): ?>
                        <option value="<?= $area['id'] ?>" <?= getPost('area_emisora_id') == $area['id'] ? 'selected' : '' ?>>
                            <?= sanitize($area['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Área Custodio</label>
                    <select name="area_custodio_id" class="form-control">
                        <option value="">Seleccionar...</option>
                        <?php foreach ($areas as $area): ?>
                        <option value="<?= $area['id'] ?>" <?= getPost('area_custodio_id') == $area['id'] ? 'selected' : '' ?>>
                            <?= sanitize($area['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Tipo de Documento</label>
                    <select name="tipo_documento_id" class="form-control">
                        <option value="">Seleccionar...</option>
                        <?php foreach ($tipos as $tipo): ?>
                        <option value="<?= $tipo['id'] ?>" <?= getPost('tipo_documento_id') == $tipo['id'] ? 'selected' : '' ?>>
                            <?= sanitize($tipo['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Número de Folios</label>
                    <input type="number" name="num_folios" class="form-control"
                           min="0" placeholder="Ej: 150"
                           value="<?= sanitize(getPost('num_folios')) ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Asunto <span class="required">*</span></label>
                <textarea name="asunto" class="form-control" required rows="3"
                          placeholder="Descripción breve del contenido del documento..."><?= sanitize(getPost('asunto')) ?></textarea>
            </div>

            <div class="form-group">
                <label>Descripción</label>
                <textarea name="descripcion" class="form-control" rows="2"
                          placeholder="Detalle adicional del documento..."><?= sanitize(getPost('descripcion')) ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Ubicación Física (Caja)</label>
                    <select name="caja_id" class="form-control">
                        <option value="">Sin ubicar</option>
                        <?php
                        $lastAmb = '';
                        foreach ($cajas as $caja):
                            $label = $caja['ambiente_nombre'] . ' / ' . $caja['estante_codigo'] . ' / N' . $caja['nivel_numero'] . ' / Caja ' . $caja['numero'];
                        ?>
                        <option value="<?= $caja['id'] ?>" <?= getPost('caja_id') == $caja['id'] ? 'selected' : '' ?>>
                            <?= sanitize($label) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <select name="estado" class="form-control">
                        <option value="disponible" <?= getPost('estado') === 'disponible' ? 'selected' : '' ?>>Disponible</option>
                        <option value="en_revision" <?= getPost('estado') === 'en_revision' ? 'selected' : '' ?>>En Revisión</option>
                        <option value="inactivo" <?= getPost('estado') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Fecha de Registro</label>
                    <input type="date" name="fecha_registro" class="form-control"
                           value="<?= sanitize(getPost('fecha_registro', date('Y-m-d'))) ?>">
                </div>
                <div class="form-group">
                    <label>Observaciones</label>
                    <input type="text" name="observaciones" class="form-control"
                           placeholder="Notas adicionales..."
                           value="<?= sanitize(getPost('observaciones')) ?>">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                        <polyline points="17 21 17 13 7 13 7 21"/>
                        <polyline points="7 3 7 8 15 8"/>
                    </svg>
                    Registrar Documento
                </button>
                <a href="<?= SITE_URL ?>/index.php" class="btn btn-outline">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once PATH_VIEWS . '/layouts/main.php';
