<?php
/**
 * ARCHIVO: documentos/registrar.php
 * REGISTRO MANUAL DE TOMOS Y DOCUMENTOS - FASE 1
 *
 * Los campos de ubicacion topografica (estante, nivel, caja, ambiente)
 * son OPCIONALES en Fase 1. Por defecto el tomo queda en:
 * 'Pendiente de Asignacion / Archivo General'
 */
require_once dirname(__DIR__) . '/config/config.php';
Auth::requireWrite();

$errors = [];
$success = '';

$areas = Area::findAll();
$tipos = TipoDocumento::findAll();

if (isPost()) {
    if (!verifyCSRF()) {
        $errors[] = 'Token de seguridad inválido.';
    } else {
        $codigo     = trim(getPost('codigo_tomo'));
        $anio       = getPost('anio');
        $area       = trim(getPost('area'));
        $tipo       = trim(getPost('tipo_documento'));

        // ---- Validacion server-side (mensajes claros) ----
        if (empty($codigo)) {
            $errors[] = 'El código del tomo es obligatorio.';
        } elseif (Tomo::existeCodigo($codigo)) {
            $errors[] = 'El código "'.$codigo.'" ya está registrado. Use un código único.';
        }

        if (empty($anio)) {
            $errors[] = 'El año es obligatorio.';
        } else {
            $anio = (int) preg_replace('/[^0-9]/', '', $anio);
            if ($anio < 1900 || $anio > (int) date('Y') + 1) {
                $errors[] = 'El año ingresado no es válido (1900 - ' . date('Y') . ').';
            }
        }

        if (empty($area)) {
            $errors[] = 'El área o dependencia es obligatoria.';
        }

        // Documentos asociados dinámicos
        $documentos = [];
        $solicitantes = $_POST['solicitante'] ?? [];
        $asuntos      = $_POST['asunto'] ?? [];
        $folios       = $_POST['folios_texto'] ?? [];
        $expedientes  = $_POST['expediente_texto'] ?? [];

        // Validar que al menos exista un asunto
        $hayAsunto = false;
        if (is_array($asuntos)) {
            foreach ($asuntos as $a) {
                if (!empty(trim($a))) { $hayAsunto = true; break; }
            }
        }
        if (!$hayAsunto) {
            $errors[] = 'Debe registrar al menos un asunto (documento) en el tomo.';
        }

        // Validar folios textuales
        foreach ($folios as $f) {
            $f = trim($f);
            if (!empty($f) && !preg_match('/^[0-9\-\s,a-zA-ZÀ-ÿ]+$/', $f)) {
                $errors[] = 'El campo folios solo admite valores como: 1-140, INDETERMINADO, SIN FOLIAR.';
                break;
            }
        }

        // Validacion por fila: toda fila con datos debe tener asunto
        if (is_array($asuntos) && is_array($solicitantes)) {
            foreach ($solicitantes as $i => $s) {
                $s = trim($s);
                $f = trim($folios[$i] ?? '');
                $e = trim($expedientes[$i] ?? '');
                $a = trim($asuntos[$i] ?? '');
                if (empty($a) && ($s !== '' || $f !== '' || $e !== '')) {
                    $errors[] = 'Cada documento debe tener un asunto. Complete o quite la fila sin asunto.';
                    break;
                }
            }
        }

        if (empty($errors)) {
            // Construir lista de documentos
            if (is_array($solicitantes)) {
                for ($i = 0; $i < count($solicitantes); $i++) {
                    $documentos[] = [
                        'solicitante'      => trim($solicitantes[$i] ?? ''),
                        'asunto'           => trim($asuntos[$i] ?? ''),
                        'folios_texto'     => trim($folios[$i] ?? ''),
                        'expediente_texto' => trim($expedientes[$i] ?? ''),
                        'anio'             => $anio
                    ];
                }
            }

            try {
                $idTomo = Tomo::create([
                    'codigo_tomo'      => $codigo,
                    'anio'             => $anio,
                    'area'             => $area,
                    'tipo_documento'   => $tipo ?: null,
                    'cantidad_folios'  => getPost('cantidad_folios') !== '' ? getPostInt('cantidad_folios') : null,
                    'observaciones'    => trim(getPost('observaciones')),
                    'usuario_registro_id' => Auth::id()
                ], $documentos);

                Audit::registrar(Auth::id(), 'tomo_registro', 'tomos', $idTomo,
                    "Tomo registrado manualmente: {$codigo}");

                flash('success', "Tomo {$codigo} registrado correctamente. Ubicación: Pendiente de Asignación / Archivo General.");
                redirect(SITE_URL . '/documentos/ver_tomo.php?id=' . $idTomo);
            } catch (Exception $e) {
                error_log('Error registrando tomo: ' . $e->getMessage());
                $errors[] = 'Error al registrar el tomo. Intente de nuevo.';
            }
        }
    }
}

$pageTitle = 'Registrar Documento';
ob_start();
?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <strong>No se pudo registrar:</strong>
    <ul style="margin:4px 0 0 16px;">
        <?php foreach ($errors as $err): ?>
        <li><?= sanitize($err) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3>Registro de Nuevo Tomo (Documento)</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="" id="formRegistro" novalidate>
            <?= csrfField() ?>

            <!-- CABECERA DEL TOMO -->
            <div class="form-row">
                <div class="form-group">
                    <label for="codigo_tomo">Código de Tomo <span class="required">*</span></label>
                    <input type="text" name="codigo_tomo" id="codigo_tomo" class="form-control"
                           placeholder="Ej: A-001, CARTAS-012"
                           value="<?= sanitize(getPost('codigo_tomo')) ?>"
                           data-requerido="El código del tomo es obligatorio.">
                    <small class="field-error" id="err_codigo_tomo"></small>
                </div>
                <div class="form-group">
                    <label for="anio">Año <span class="required">*</span></label>
                    <input type="number" name="anio" id="anio" class="form-control"
                           min="1900" max="<?= date('Y') ?>"
                           value="<?= sanitize(getPost('anio', date('Y'))) ?>"
                           data-requerido="El año es obligatorio.">
                    <small class="field-error" id="err_anio"></small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="area">Área / Dependencia <span class="required">*</span></label>
                    <select name="area" id="area" class="form-control"
                            data-requerido="Seleccione el área o escriba una nueva.">
                        <option value="">Seleccionar o escribir...</option>
                        <?php foreach (Tomo::areas() as $areaEx): ?>
                        <option value="<?= sanitize($areaEx) ?>" <?= getPost('area') === $areaEx ? 'selected' : '' ?>>
                            <?= sanitize($areaEx) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="field-error" id="err_area"></small>
                </div>
                <div class="form-group">
                    <label for="tipo_documento">Tipo de Documento</label>
                    <select name="tipo_documento" id="tipo_documento" class="form-control">
                        <option value="">Seleccionar o escribir...</option>
                        <?php foreach ($tipos as $tipo): ?>
                        <option value="<?= sanitize($tipo['nombre']) ?>" <?= getPost('tipo_documento') === $tipo['nombre'] ? 'selected' : '' ?>>
                            <?= sanitize($tipo['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="cantidad_folios">Cantidad de Folios</label>
                    <input type="number" name="cantidad_folios" id="cantidad_folios" class="form-control"
                           min="0" placeholder="Ej: 150"
                           value="<?= sanitize(getPost('cantidad_folios')) ?>">
                </div>
                <div class="form-group">
                    <label>Estado de Ubicación Física</label>
                    <div class="location-default">
                        <span class="badge badge-info">Pendiente de Asignación / Archivo General</span>
                        <small style="color:var(--text-muted); display:block; margin-top:4px;">
                            La ubicación topográfica (estante, nivel, caja, ambiente) se organizará en la Fase 2.
                        </small>
                    </div>
                </div>
            </div>

            <!-- DOCUMENTOS DEL TOMO (dinamico) -->
            <div style="margin-top:20px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <h4 style="margin:0; font-size:15px;">Documentos del Tomo</h4>
                    <button type="button" class="btn btn-outline" onclick="agregarFila()" style="padding:5px 12px; font-size:13px;">
                        + Agregar Documento
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table-main" id="tablaDocs">
                        <thead>
                            <tr>
                                <th style="width:22%;">Solicitante</th>
                                <th style="width:18%;">Folios (texto)</th>
                                <th style="width:15%;">Expediente(s)</th>
                                <th>Asunto <span class="required">*</span></th>
                                <th style="width:40px;"></th>
                            </tr>
                        </thead>
                        <tbody id="tbodyDocs">
                            <tr class="fila-doc">
                                <td>
                                    <input type="text" name="solicitante[]" class="form-control"
                                           placeholder="Nombre del solicitante">
                                </td>
                                <td>
                                    <input type="text" name="folios_texto[]" class="form-control"
                                           placeholder="1-140, INDETERMINADO, SIN FOLIAR">
                                </td>
                                <td>
                                    <input type="text" name="expediente_texto[]" class="form-control"
                                           placeholder="Ej: 1036|1342">
                                </td>
                                <td>
                                    <input type="text" name="asunto[]" class="form-control"
                                           placeholder="Descripción del documento" required
                                           data-requerido="El asunto es obligatorio.">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline btn-quitar"
                                            onclick="quitarFila(this)" title="Quitar fila">×</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <small class="field-error" id="err_asunto_doc"></small>
                </div>
            </div>

            <div class="form-group" style="margin-top:16px;">
                <label for="observaciones">Observaciones</label>
                <textarea name="observaciones" id="observaciones" class="form-control" rows="2"
                          placeholder="Notas adicionales..."><?= sanitize(getPost('observaciones')) ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="btnSubmit">
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

<style>
.field-error { color: var(--danger); font-size: 12px; display: block; margin-top: 3px; }
.invalid { border-color: var(--danger) !important; }
.location-default {
    background: var(--info-light);
    padding: 10px 14px;
    border-radius: var(--radius);
    min-height: 38px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.btn-quitar {
    padding: 4px 8px; line-height: 1; font-size: 15px;
    color: var(--danger); border-color: var(--danger);
}
.btn-quitar:hover { background: var(--danger); color: #fff; }
</style>

<script>
// ---- Validacion HTML5 mejorada ----
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('formRegistro');

    var inputs = form.querySelectorAll('[data-requerido]');
    inputs.forEach(function(input) {
        input.addEventListener('input', function() {
            if (this.value.trim() !== '') {
                this.classList.remove('invalid');
                var err = document.getElementById('err_' + this.name.replace('.', '_'));
                if (err) err.textContent = '';
            }
        });
    });

    form.addEventListener('submit', function(e) {
        var ok = true;

        // Validar campos requeridos
        inputs.forEach(function(input) {
            var valor = input.value.trim();
            if (valor === '' || (input.type === 'select-one' && valor === '')) {
                input.classList.add('invalid');
                var msj = input.getAttribute('data-requerido') || 'Complete este campo.';
                var err = document.getElementById('err_' + input.name.replace('.', '_'));
                if (err) err.textContent = msj;
                ok = false;
            }
        });

        // Validar que al menos un asunto este lleno
        var asuntos = form.querySelectorAll('input[name="asunto[]"]');
        var hayAsunto = false;
        asuntos.forEach(function(a) {
            if (a.value.trim() !== '') hayAsunto = true;
        });
        if (!hayAsunto) {
            document.getElementById('err_asunto_doc').textContent =
                'Debe registrar al menos un asunto (documento) en el tomo.';
            asuntos[0].classList.add('invalid');
            ok = false;
        } else {
            document.getElementById('err_asunto_doc').textContent = '';
        }

        if (!ok) {
            e.preventDefault();
            var firstErr = form.querySelector('.invalid');
            if (firstErr) firstErr.focus();
        }
    });
});

function agregarFila() {
    var tbody = document.getElementById('tbodyDocs');
    var row = document.createElement('tr');
    row.className = 'fila-doc';
    row.innerHTML =
        '<td><input type="text" name="solicitante[]" class="form-control" placeholder="Nombre del solicitante"></td>' +
        '<td><input type="text" name="folios_texto[]" class="form-control" placeholder="1-140, INDETERMINADO, SIN FOLIAR"></td>' +
        '<td><input type="text" name="expediente_texto[]" class="form-control" placeholder="Ej: 1036|1342"></td>' +
        '<td><input type="text" name="asunto[]" class="form-control" placeholder="Descripción del documento" required data-requerido="El asunto es obligatorio."></td>' +
        '<td><button type="button" class="btn btn-sm btn-outline btn-quitar" onclick="quitarFila(this)" title="Quitar fila">×</button></td>';
    tbody.appendChild(row);
}

function quitarFila(btn) {
    var filas = document.querySelectorAll('.fila-doc');
    if (filas.length <= 1) return; // mantener al menos una fila
    btn.closest('tr').remove();
}
</script>

<?php
$content = ob_get_clean();
require_once PATH_VIEWS . '/layouts/main.php';
