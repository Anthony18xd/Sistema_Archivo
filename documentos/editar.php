<?php
/**
 * ARCHIVO: documentos/editar.php
 * EDICION DE DOCUMENTO
 */
require_once dirname(__DIR__) . '/config/config.php';
Auth::requireWrite();

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

$errors = [];
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
            'estado'            => getPost('estado'),
            'usuario_id'        => Auth::id()
        ];

        if (empty($data['codigo'])) $errors[] = 'El código es obligatorio.';
        if (strlen($data['codigo']) > 50) $errors[] = 'El código no puede superar 50 caracteres.';
        if (empty($data['asunto'])) $errors[] = 'El asunto es obligatorio.';
        if (strlen($data['asunto']) > 500) $errors[] = 'El asunto no puede superar 500 caracteres.';
        if (empty($data['anio'])) $errors[] = 'El año es obligatorio.';
        elseif (!esEnteroOpcional($data['anio'], 1900, (int) date('Y') + 1)) {
            $errors[] = 'El año ingresado no es válido (1900 - ' . date('Y') . ').';
        }
        if (!esUnoDe($data['estado'], ['disponible', 'prestado', 'en_revision', 'inactivo'])) {
            $errors[] = 'El estado no es válido.';
        }
        if (!esEnteroOpcional($data['num_folios'], 0, 100000)) {
            $errors[] = 'El número de folios no es válido.';
        }
        if (!empty($data['area_emisora_id']) && !existeRegistro('areas', 'id', (int) $data['area_emisora_id'])) {
            $errors[] = 'El área emisora seleccionada no es válida.';
        }
        if (!empty($data['area_custodio_id']) && !existeRegistro('areas', 'id', (int) $data['area_custodio_id'])) {
            $errors[] = 'El área custodio seleccionada no es válida.';
        }
        if (!empty($data['tipo_documento_id']) && !existeRegistro('tipos_documento', 'id', (int) $data['tipo_documento_id'])) {
            $errors[] = 'El tipo de documento seleccionado no es válido.';
        }
        if (!empty($data['caja_id']) && !existeRegistro('cajas', 'id', (int) $data['caja_id'])) {
            $errors[] = 'La caja seleccionada no es válida.';
        }

        if (!empty($data['codigo']) && Documento::existeCodigo($data['codigo'], (int)$id)) {
            $errors[] = 'El código ya está registrado por otro documento.';
        }

        if (empty($errors)) {
            $result = Documento::update((int)$id, $data);
            if ($result) {
                Audit::registrar(Auth::id(), 'documento_modificacion', 'documentos', (int)$id,
                    "Documento modificado: {$data['codigo']}");
                flash('success', "Documento {$data['codigo']} actualizado correctamente.");
                redirect(SITE_URL . '/documentos/ver.php?id=' . $id);
            } else {
                $errors[] = 'Error al actualizar el documento.';
            }
        }
    }
}

$pageTitle = 'Editar: ' . $documento['codigo'];
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
        <h3>Editar Documento: <?= sanitize($documento['codigo']) ?></h3>
        <a href="<?= SITE_URL ?>/documentos/ver.php?id=<?= $id ?>" class="btn btn-sm btn-outline">Volver</a>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <?= csrfField() ?>

            <div class="form-row">
                <div class="form-group">
                    <label>Código <span class="required">*</span></label>
                    <input type="text" name="codigo" class="form-control" required
                           value="<?= sanitize(getPost('codigo', $documento['codigo'])) ?>">
                </div>
                <div class="form-group">
                    <label>Año <span class="required">*</span></label>
                    <input type="number" name="anio" class="form-control" required
                           min="1900" max="<?= date('Y') ?>"
                           value="<?= sanitize(getPost('anio', $documento['anio'])) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Área Emisora</label>
                    <select name="area_emisora_id" class="form-control">
                        <option value="">Seleccionar...</option>
                        <?php foreach ($areas as $area): ?>
                        <option value="<?= $area['id'] ?>" <?= (getPost('area_emisora_id', $documento['area_emisora_id']) == $area['id']) ? 'selected' : '' ?>>
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
                        <option value="<?= $area['id'] ?>" <?= (getPost('area_custodio_id', $documento['area_custodio_id']) == $area['id']) ? 'selected' : '' ?>>
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
                        <option value="<?= $tipo['id'] ?>" <?= (getPost('tipo_documento_id', $documento['tipo_documento_id']) == $tipo['id']) ? 'selected' : '' ?>>
                            <?= sanitize($tipo['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Número de Folios</label>
                    <input type="number" name="num_folios" class="form-control" min="0"
                           value="<?= sanitize(getPost('num_folios', $documento['num_folios'])) ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Asunto <span class="required">*</span></label>
                <textarea name="asunto" class="form-control" required rows="3"><?= sanitize(getPost('asunto', $documento['asunto'])) ?></textarea>
            </div>

            <div class="form-group">
                <label>Descripción</label>
                <textarea name="descripcion" class="form-control" rows="2"><?= sanitize(getPost('descripcion', $documento['descripcion'])) ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Ubicación Física (Caja)</label>
                    <select name="caja_id" class="form-control">
                        <option value="">Sin ubicar</option>
                        <?php foreach ($cajas as $caja):
                            $label = $caja['ambiente_nombre'] . ' / ' . $caja['estante_codigo'] . ' / N' . $caja['nivel_numero'] . ' / Caja ' . $caja['numero'];
                        ?>
                        <option value="<?= $caja['id'] ?>" <?= (getPost('caja_id', $documento['caja_id']) == $caja['id']) ? 'selected' : '' ?>>
                            <?= sanitize($label) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <select name="estado" class="form-control">
                        <option value="disponible" <?= getPost('estado', $documento['estado']) === 'disponible' ? 'selected' : '' ?>>Disponible</option>
                        <option value="prestado" <?= getPost('estado', $documento['estado']) === 'prestado' ? 'selected' : '' ?>>Prestado</option>
                        <option value="en_revision" <?= getPost('estado', $documento['estado']) === 'en_revision' ? 'selected' : '' ?>>En Revisión</option>
                        <option value="inactivo" <?= getPost('estado', $documento['estado']) === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Observaciones</label>
                <input type="text" name="observaciones" class="form-control"
                       value="<?= sanitize(getPost('observaciones', $documento['observaciones'])) ?>">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                        <polyline points="17 21 17 13 7 13 7 21"/>
                        <polyline points="7 3 7 8 15 8"/>
                    </svg>
                    Guardar Cambios
                </button>
                <a href="<?= SITE_URL ?>/documentos/ver.php?id=<?= $id ?>" class="btn btn-outline">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once PATH_VIEWS . '/layouts/main.php';
