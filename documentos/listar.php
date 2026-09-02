<?php
/**
 * ARCHIVO: documentos/listar.php
 * INVENTARIO DE TOMOS - FASE 1
 * Listado general de tomos del archivo municipal con accion de
 * eliminacion (SOLO ADMINISTRADOR).
 */
require_once dirname(__DIR__) . '/config/config.php';
Auth::requireLogin();

PrestamoFase1::actualizarEstadoVencidos();

$page = max(1, (int) getQuery('page', 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$filtros = [];
if (!empty($_GET['anio'])) $filtros['anio'] = getQuery('anio');
if (!empty($_GET['area'])) $filtros['area'] = getQuery('area');

$termino = getQuery('q', '');
$tomos = Tomo::buscar($termino, $filtros, $perPage, $offset);
$total = Tomo::contarBusqueda($termino, $filtros);
$totalPages = max(1, (int) ceil($total / $perPage));

$areasDisponibles = Tomo::areas();
$aniosDisponibles = Tomo::anios();

$pageTitle = 'Inventario de Tomos';
ob_start();
?>
<style>
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
.modal-footer {
    padding: 14px 20px;
    border-top: 1px solid var(--border-light);
    display: flex;
    gap: 8px;
    justify-content: flex-end;
}
</style>

<div class="card">
    <div class="card-header">
        <h3>Inventario de Tomos (<?= formatNumber($total) ?> total)</h3>
        <?php if (Auth::canWrite()): ?>
        <a href="<?= SITE_URL ?>/documentos/registrar.php" class="btn btn-sm btn-primary">+ Nuevo Tomo</a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <div class="search-box">
                <input type="text" name="q" class="form-control" placeholder="Buscar por código, solicitante, expediente, asunto..."
                       value="<?= sanitize($termino) ?>">
                <select name="anio" class="form-control" style="max-width:110px;">
                    <option value="">Año</option>
                    <?php foreach ($aniosDisponibles as $anio): ?>
                    <option value="<?= $anio ?>" <?= getQuery('anio') == $anio ? 'selected' : '' ?>><?= $anio ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="area" class="form-control" style="max-width:200px;">
                    <option value="">Todas las áreas</option>
                    <?php foreach ($areasDisponibles as $area): ?>
                    <option value="<?= sanitize($area) ?>" <?= getQuery('area') === $area ? 'selected' : '' ?>><?= sanitize($area) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
            </div>
        </form>
    </div>
    <div class="card-body" style="padding:0;">
        <?php if (empty($tomos)): ?>
            <div class="empty-state">
                <p>No hay tomos registrados.</p>
            </div>
        <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Código Tomo</th>
                        <th>Año</th>
                        <th>Área</th>
                        <th>Tipo</th>
                        <th>Docs</th>
                        <th>Ubicación</th>
                        <th>Estado</th>
                        <th style="text-align:center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tomos as $tomo):
                        $estaPrestado = ($tomo['estado_prestamo'] === 'prestado');
                    ?>
                    <tr>
                        <td><strong style="font-family:monospace;"><?= sanitize($tomo['codigo_tomo']) ?></strong></td>
                        <td><?= $tomo['anio'] ? sanitize($tomo['anio']) : '-' ?></td>
                        <td title="<?= sanitize($tomo['area'] ?? '') ?>"><?= sanitize(mb_strimwidth($tomo['area'] ?? '-', 0, 25, '...')) ?></td>
                        <td><?= sanitize($tomo['tipo_documento'] ?? '-') ?></td>
                        <td><?= (int)$tomo['total_documentos'] ?></td>
                        <td>
                            <?php if ($tomo['ubicacion_estado'] === 'pendiente_asignacion'): ?>
                                <span class="badge badge-info">Pend. Asignación</span>
                            <?php else: ?>
                                <span class="badge badge-default"><?= ucfirst(str_replace('_', ' ', $tomo['ubicacion_estado'])) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($estaPrestado): ?>
                                <span class="badge badge-warning">Prestado</span>
                            <?php else: ?>
                                <span class="badge badge-success">Disponible</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:center; white-space:nowrap;">
                            <a href="<?= SITE_URL ?>/documentos/ver_tomo.php?id=<?= $tomo['id_tomo'] ?>"
                               class="btn btn-sm btn-outline" title="Ver detalle">Ver</a>
                            <?php if (Auth::isAdmin()): ?>
                            <button class="btn btn-sm btn-danger"
                                    onclick="confirmarEliminar(<?= $tomo['id_tomo'] ?>, '<?= sanitize(addslashes($tomo['codigo_tomo'])) ?>')"
                                    title="Eliminar tomo (solo administrador)"
                                    style="margin-left:4px; padding:5px 10px; font-size:12px;">
                                <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                    <line x1="10" y1="11" x2="10" y2="17"/>
                                    <line x1="14" y1="11" x2="14" y2="17"/>
                                </svg>
                                Eliminar
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <div style="padding:16px; display:flex; justify-content:center; gap:6px;">
            <?php for ($i = max(1, $page - 3); $i <= min($totalPages, $page + 3); $i++): ?>
            <a href="?page=<?= $i ?>&q=<?= urlencode($termino) ?>&anio=<?= urlencode(getQuery('anio')) ?>&area=<?= urlencode(getQuery('area')) ?>"
               class="btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-outline' ?>">
                <?= $i ?>
            </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL: CONFIRMAR ELIMINACION (SOLO ADMINISTRADOR) -->
<?php if (Auth::isAdmin()): ?>
<div class="modal-overlay" id="modalEliminar">
    <div class="modal-box" style="max-width:420px;">
        <div class="modal-header">
            <h3>Eliminar Tomo</h3>
            <button class="modal-close" onclick="document.getElementById('modalEliminar').classList.remove('active')">&times;</button>
        </div>
        <form method="POST" action="<?= SITE_URL ?>/documentos/eliminar_tomo.php" id="formEliminar">
            <?= csrfField() ?>
            <input type="hidden" name="id_tomo" id="eliminar_id_tomo">
            <input type="hidden" name="volver" value="<?= sanitize(SITE_URL . '/documentos/listar.php' . (strlen($termino) ? '?q=' . urlencode($termino) : '')) ?>">
            <div class="modal-body">
                <div class="alert alert-danger" style="margin:0 0 12px;">
                    <strong>Atención:</strong> Esta acción eliminará permanentemente el tomo y sus documentos.
                    No puede deshacerse.
                </div>
                <div style="background:var(--bg); padding:10px 14px; border-radius:var(--radius);">
                    <strong style="color:var(--primary);">Tomo:</strong>
                    <span id="eliminar_codigo_display" style="font-family:monospace;"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modalEliminar').classList.remove('active')">Cancelar</button>
                <button type="submit" class="btn btn-danger">Sí, Eliminar</button>
            </div>
        </form>
    </div>
</div>
<script>
function confirmarEliminar(idTomo, codigo) {
    document.getElementById('eliminar_id_tomo').value = idTomo;
    document.getElementById('eliminar_codigo_display').textContent = codigo;
    document.getElementById('modalEliminar').classList.add('active');
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
        if (e.target === overlay) overlay.classList.remove('active');
    });
});
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once PATH_VIEWS . '/layouts/main.php';
