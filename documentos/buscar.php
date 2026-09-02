<?php
/**
 * ARCHIVO: documentos/buscar.php
 * BUSQUEDA PRINCIPAL Y GESTION DE PRESTAMOS - FASE 1
 *
 * Buscador unificado que busca simultaneamente en:
 * - Solicitante, Numero de Expediente, Asunto, Codigo de Tomo
 *
 * Filtros opcionales: Ano, Area
 * Accion rapida: Registrar Prestamo desde la tabla de resultados
 */
require_once dirname(__DIR__) . '/config/config.php';
Auth::requireLogin();

// Actualizar estados de prestamos vencidos
PrestamoFase1::actualizarEstadoVencidos();

$termino = getQuery('q', '');
$filtros = [];
$tomos = [];
$total = 0;

if (!empty($termino) || !empty($_GET['anio']) || !empty($_GET['area'])) {
    if (!empty($_GET['anio']))  $filtros['anio'] = getQuery('anio');
    if (!empty($_GET['area']))  $filtros['area'] = getQuery('area');

    $tomos = Tomo::buscar($termino, $filtros, 50, 0);
    $total = Tomo::contarBusqueda($termino, $filtros);
}

$areasDisponibles = Tomo::areas();
$aniosDisponibles = Tomo::anios();
$stats = Tomo::estadisticas();

$pageTitle = 'Buscar Documento';
ob_start();
?>

<style>
.search-hero {
    background: linear-gradient(135deg, var(--primary-dark), var(--primary));
    border-radius: var(--radius-lg);
    padding: 32px;
    margin-bottom: 20px;
    color: #fff;
}
.search-hero h2 {
    font-size: 20px;
    margin-bottom: 4px;
}
.search-hero p {
    opacity: 0.7;
    font-size: 13px;
    margin-bottom: 20px;
}
.search-bar {
    display: flex;
    gap: 8px;
    max-width: 700px;
}
.search-bar input {
    flex: 1;
    padding: 12px 16px;
    border: none;
    border-radius: var(--radius);
    font-size: 15px;
    outline: none;
}
.search-bar button {
    padding: 12px 24px;
    background: var(--accent);
    color: var(--primary-dark);
    border: none;
    border-radius: var(--radius);
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 14px;
    transition: background 0.2s;
}
.search-bar button:hover { background: var(--accent-dark); }
.mini-stats {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}
.mini-stat {
    background: var(--card);
    border-radius: var(--radius);
    padding: 14px 16px;
    text-align: center;
    box-shadow: var(--shadow);
}
.mini-stat .num {
    font-size: 22px;
    font-weight: 700;
    color: var(--primary);
}
.mini-stat .lbl {
    font-size: 11px;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.mini-stat.stat-warn .num { color: var(--warning); }
.mini-stat.stat-danger .num { color: var(--danger); }
.mini-stat.stat-success .num { color: var(--success); }
.filters-bar {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: end;
    margin-top: 12px;
}
.filters-bar .form-group { margin-bottom: 0; }
.filters-bar label {
    font-size: 11px;
    color: rgba(255,255,255,0.6);
    text-transform: uppercase;
    letter-spacing: 0.3px;
    margin-bottom: 3px;
    display: block;
}
.filters-bar select,
.filters-bar input[type="number"] {
    padding: 8px 10px;
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: var(--radius);
    background: rgba(255,255,255,0.1);
    color: #fff;
    font-size: 13px;
    min-width: 140px;
}
.filters-bar select option { color: #333; background: #fff; }
.btn-clear {
    padding: 8px 16px;
    background: rgba(255,255,255,0.15);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: var(--radius);
    cursor: pointer;
    font-size: 13px;
    text-decoration: none;
}
.btn-clear:hover { background: rgba(255,255,255,0.25); color: #fff; }
.result-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.result-header h3 { margin: 0; }
.table-responsive { overflow-x: auto; }
.table-main { width: 100%; border-collapse: collapse; }
.table-main th {
    background: var(--bg);
    padding: 10px 12px;
    text-align: left;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: var(--text-light);
    border-bottom: 2px solid var(--border);
    white-space: nowrap;
}
.table-main td {
    padding: 10px 12px;
    border-bottom: 1px solid var(--border-light);
    font-size: 13px;
    vertical-align: middle;
}
.table-main tr:hover { background: rgba(26,58,92,0.03); }
.tomo-code {
    font-weight: 700;
    color: var(--primary);
    font-family: 'Courier New', monospace;
    font-size: 13px;
}
.folio-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
    background: var(--info-light);
    color: var(--info);
}
.folio-badge.folio-indeterminado {
    background: var(--warning-light);
    color: var(--warning);
}
.folio-badge.folio-sin-foliar {
    background: var(--danger-light);
    color: var(--danger);
}
.expedientes-list {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}
.exp-badge {
    display: inline-block;
    padding: 1px 6px;
    border-radius: 3px;
    font-size: 11px;
    background: #f0f0f0;
    color: #555;
    font-family: 'Courier New', monospace;
}
.btn-prestamo {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 5px 12px;
    background: var(--warning);
    color: #fff;
    border: none;
    border-radius: var(--radius);
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
    white-space: nowrap;
}
.btn-prestamo:hover { background: var(--accent-dark); }
.btn-prestamo:disabled {
    background: var(--text-muted);
    cursor: not-allowed;
    opacity: 0.6;
}
.btn-prestamo.btn-devolver {
    background: var(--success);
}
.btn-prestamo.btn-devolver:hover { background: #047857; }
.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 200;
    justify-content: center;
    align-items: center;
}
.modal-overlay.active { display: flex; }
.modal-box {
    background: var(--card);
    border-radius: var(--radius-lg);
    width: 480px;
    max-width: 95vw;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: var(--shadow-lg);
}
.modal-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border-light);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-header h3 { margin: 0; font-size: 16px; }
.modal-close {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: var(--text-muted);
    padding: 4px 8px;
}
.modal-body { padding: 20px; }
.modal-body .form-group { margin-bottom: 14px; }
.modal-body .form-group label {
    font-size: 12px;
    font-weight: 600;
    color: var(--text-light);
    margin-bottom: 4px;
    display: block;
}
.modal-footer {
    padding: 14px 20px;
    border-top: 1px solid var(--border-light);
    display: flex;
    gap: 8px;
    justify-content: flex-end;
}
.spinner {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
@media (max-width: 768px) {
    .mini-stats { grid-template-columns: repeat(2, 1fr); }
    .search-bar { flex-direction: column; }
    .filters-bar { flex-direction: column; }
    .filters-bar select,
    .filters-bar input[type="number"] { min-width: 100%; }
}
</style>

<!-- MINI ESTADISTICAS -->
<div class="mini-stats">
    <div class="mini-stat">
        <div class="num"><?= formatNumber($stats['total_tomos']) ?></div>
        <div class="lbl">Tomos</div>
    </div>
    <div class="mini-stat stat-success">
        <div class="num"><?= formatNumber($stats['total_documentos']) ?></div>
        <div class="lbl">Documentos</div>
    </div>
    <div class="mini-stat stat-warn">
        <div class="num"><?= formatNumber($stats['prestamos_activos']) ?></div>
        <div class="lbl">Prestamos Activos</div>
    </div>
    <div class="mini-stat stat-danger">
        <div class="num"><?= formatNumber($stats['prestamos_vencidos']) ?></div>
        <div class="lbl">Vencidos</div>
    </div>
    <div class="mini-stat">
        <div class="num"><?= formatNumber($stats['pendientes_asignacion']) ?></div>
        <div class="lbl">Pendientes Asignacion</div>
    </div>
</div>

<!-- BUSCADOR PRINCIPAL -->
<div class="search-hero">
    <h2>Busqueda de Archivo Municipal</h2>
    <p>Busque por solicitante, numero de expediente, asunto o codigo de tomo</p>
    <form method="GET" action="">
        <div class="search-bar">
            <input type="text" name="q" placeholder="Escriba un termino de busqueda..."
                   value="<?= sanitize($termino) ?>" autofocus autocomplete="off" id="searchInput">
            <button type="submit">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                Buscar
            </button>
        </div>

        <div class="filters-bar">
            <div class="form-group">
                <label>Ano</label>
                <select name="anio">
                    <option value="">Todos los anios</option>
                    <?php foreach ($aniosDisponibles as $anio): ?>
                    <option value="<?= $anio ?>" <?= getQuery('anio') == $anio ? 'selected' : '' ?>><?= $anio ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Area / Dependencia</label>
                <select name="area">
                    <option value="">Todas las areas</option>
                    <?php foreach ($areasDisponibles as $area): ?>
                    <option value="<?= sanitize($area) ?>" <?= getQuery('area') === $area ? 'selected' : '' ?>><?= sanitize($area) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if (!empty($termino) || !empty($_GET['anio']) || !empty($_GET['area'])): ?>
            <a href="<?= SITE_URL ?>/documentos/buscar.php" class="btn-clear">Limpiar filtros</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- RESULTADOS -->
<?php if (!empty($termino) || !empty($_GET['anio']) || !empty($_GET['area'])): ?>
<div class="card">
    <div class="card-header">
        <div class="result-header">
            <h3>Resultados: <?= formatNumber($total) ?> tomo<?= $total !== 1 ? 's' : '' ?></h3>
        </div>
    </div>
    <div class="card-body" style="padding:0;">
        <?php if (empty($tomos)): ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <p>No se encontraron resultados para "<strong><?= sanitize($termino) ?></strong>"</p>
                <small style="color:var(--text-muted);">Intente con otros terminos o verifique los filtros.</small>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table-main">
                <thead>
                    <tr>
                        <th>Codigo Tomo</th>
                        <th>Ano</th>
                        <th>Area</th>
                        <th>Solicitante(s)</th>
                        <th>Folios</th>
                        <th>Expediente(s)</th>
                        <th>Estado</th>
                        <th style="text-align:center;">Accion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tomos as $tomo):
                        // Obtener documentos de este tomo
                        $docsTomo = DocumentoFase1::findByTomo($tomo['id_tomo']);

                        // Recopilar solicitantes unicos
                        $solicitantes = [];
                        $foliosMostrar = [];
                        $expedientesMostrar = [];

                        foreach ($docsTomo as $doc) {
                            if (!empty($doc['solicitante']) && !in_array($doc['solicitante'], $solicitantes)) {
                                $solicitantes[] = $doc['solicitante'];
                            }
                            if (!empty($doc['folios_texto'])) {
                                $foliosMostrar[] = $doc['folios_texto'];
                            }
                            if (!empty($doc['expediente_texto'])) {
                                $expParts = explode('|', $doc['expediente_texto']);
                                foreach ($expParts as $ep) {
                                    $ep = trim($ep);
                                    if (!empty($ep) && !in_array($ep, $expedientesMostrar)) {
                                        $expedientesMostrar[] = $ep;
                                    }
                                }
                            }
                        }

                        $estaPrestado = ($tomo['estado_prestamo'] === 'prestado');
                    ?>
                    <tr>
                        <td>
                            <span class="tomo-code"><?= sanitize($tomo['codigo_tomo']) ?></span>
                        </td>
                        <td><?= $tomo['anio'] ? sanitize($tomo['anio']) : '-' ?></td>
                        <td title="<?= sanitize($tomo['area'] ?? '') ?>"><?= sanitize(mb_strimwidth($tomo['area'] ?? '-', 0, 30, '...')) ?></td>
                        <td>
                            <?php if (!empty($solicitantes)): ?>
                                <?php foreach (array_slice($solicitantes, 0, 3) as $s): ?>
                                    <div style="font-size:12px; line-height:1.4;"><?= sanitize(mb_strimwidth($s, 0, 35, '...')) ?></div>
                                <?php endforeach; ?>
                                <?php if (count($solicitantes) > 3): ?>
                                    <div style="font-size:11px; color:var(--text-muted);">+<?= count($solicitantes) - 3 ?> mas</div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color:var(--text-muted);">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($foliosMostrar)):
                                $folioUnico = $foliosMostrar[0];
                            ?>
                                <?php if (stripos($folioUnico, 'INDETERMINADO') !== false): ?>
                                    <span class="folio-badge folio-indeterminado"><?= sanitize($folioUnico) ?></span>
                                <?php elseif (stripos($folioUnico, 'SIN FOLIAR') !== false): ?>
                                    <span class="folio-badge folio-sin-foliar"><?= sanitize($folioUnico) ?></span>
                                <?php else: ?>
                                    <span class="folio-badge"><?= sanitize($folioUnico) ?></span>
                                <?php endif; ?>
                                <?php if (count($foliosMostrar) > 1): ?>
                                    <span style="font-size:10px; color:var(--text-muted);">+<?= count($foliosMostrar) - 1 ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color:var(--text-muted);">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="expedientes-list">
                                <?php foreach (array_slice($expedientesMostrar, 0, 4) as $exp): ?>
                                    <span class="exp-badge"><?= sanitize($exp) ?></span>
                                <?php endforeach; ?>
                                <?php if (count($expedientesMostrar) > 4): ?>
                                    <span class="exp-badge">+<?= count($expedientesMostrar) - 4 ?></span>
                                <?php endif; ?>
                                <?php if (empty($expedientesMostrar)): ?>
                                    <span style="color:var(--text-muted);">-</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($estaPrestado): ?>
                                <span class="badge badge-warning">Prestado</span>
                            <?php else: ?>
                                <span class="badge badge-success">Disponible</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:center; white-space:nowrap;">
                            <?php if (Auth::canWrite()): ?>
                                <?php if ($estaPrestado): ?>
                                    <button class="btn-prestamo btn-devolver"
                                            onclick="abrirModalDevolver(<?= $tomo['id_tomo'] ?>, '<?= sanitize(addslashes($tomo['codigo_tomo'])) ?>')"
                                            title="Devolver tomo">
                                        <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="1 4 1 10 7 10"/>
                                            <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                                        </svg>
                                        Devolver
                                    </button>
                                <?php else: ?>
                                    <button class="btn-prestamo"
                                            onclick="abrirModalPrestamo(<?= $tomo['id_tomo'] ?>, '<?= sanitize(addslashes($tomo['codigo_tomo'])) ?>')"
                                            title="Registrar prestamo">
                                        <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="9 11 12 14 22 4"/>
                                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                                        </svg>
                                        Prestar
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                            <a href="<?= SITE_URL ?>/documentos/ver_tomo.php?id=<?= $tomo['id_tomo'] ?>"
                               class="btn btn-sm btn-outline" title="Ver detalle" style="margin-left:4px;">
                                Detalle
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- MODAL: REGISTRAR PRESTAMO -->
<div class="modal-overlay" id="modalPrestamo">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Registrar Prestamo</h3>
            <button class="modal-close" onclick="cerrarModal('modalPrestamo')">&times;</button>
        </div>
        <form method="POST" action="<?= SITE_URL ?>/prestamos/registrar_prestamo.php" id="formPrestamo">
            <?= csrfField() ?>
            <input type="hidden" name="id_tomo" id="prestamo_id_tomo">
            <div class="modal-body">
                <div style="background:var(--bg); padding:10px 14px; border-radius:var(--radius); margin-bottom:14px;">
                    <strong style="color:var(--primary);">Tomo:</strong>
                    <span id="prestamo_codigo_display" style="font-family:monospace;"></span>
                </div>
                <div class="form-group">
                    <label>Solicitante *</label>
                    <input type="text" name="solicitante_prestamo" class="form-control" required
                           placeholder="Nombre completo del solicitante">
                </div>
                <div class="form-group">
                    <label>Area de Destino</label>
                    <input type="text" name="area_destino" class="form-control"
                           placeholder="Area o dependencia a la que se lleva">
                </div>
                <div class="form-group">
                    <label>Fecha de Salida *</label>
                    <input type="date" name="fecha_salida" class="form-control"
                           value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label>Fecha Estimada de Devolucion *</label>
                    <input type="date" name="fecha_devolucion" class="form-control"
                           value="<?= date('Y-m-d', strtotime('+7 days')) ?>" required>
                </div>
                <div class="form-group">
                    <label>Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="2"
                              placeholder="Estado del tomo, observaciones..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="cerrarModal('modalPrestamo')">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnRegistrarPrestamo">
                    Registrar Prestamo
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: DEVOLVER TOMO -->
<div class="modal-overlay" id="modalDevolver">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Devolver Tomo</h3>
            <button class="modal-close" onclick="cerrarModal('modalDevolver')">&times;</button>
        </div>
        <form method="POST" action="<?= SITE_URL ?>/prestamos/devolver_tomo.php" id="formDevolver">
            <?= csrfField() ?>
            <input type="hidden" name="id_tomo" id="devolver_id_tomo">
            <div class="modal-body">
                <div style="background:var(--bg); padding:10px 14px; border-radius:var(--radius); margin-bottom:14px;">
                    <strong style="color:var(--primary);">Tomo:</strong>
                    <span id="devolver_codigo_display" style="font-family:monospace;"></span>
                </div>
                <div class="form-group">
                    <label>Fecha de Devolucion *</label>
                    <input type="date" name="fecha_devolucion" class="form-control"
                           value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label>Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="2"
                              placeholder="Estado del tomo al devolver..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="cerrarModal('modalDevolver')">Cancelar</button>
                <button type="submit" class="btn btn-success">Confirmar Devolucion</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalPrestamo(idTomo, codigo) {
    document.getElementById('prestamo_id_tomo').value = idTomo;
    document.getElementById('prestamo_codigo_display').textContent = codigo;
    document.getElementById('modalPrestamo').classList.add('active');
    document.querySelector('#modalPrestamo input[name="solicitante_prestamo"]').focus();
}

function abrirModalDevolver(idTomo, codigo) {
    document.getElementById('devolver_id_tomo').value = idTomo;
    document.getElementById('devolver_codigo_display').textContent = codigo;
    document.getElementById('modalDevolver').classList.add('active');
}

function cerrarModal(id) {
    document.getElementById(id).classList.remove('active');
}

// Cerrar modal con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(function(m) {
            m.classList.remove('active');
        });
    }
});

// Cerrar modal al hacer clic fuera
document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            overlay.classList.remove('active');
        }
    });
});

// Auto-submit deshabilitado - requiere boton Buscar
</script>

<?php
$content = ob_get_clean();
require_once PATH_VIEWS . '/layouts/main.php';
