<?php
/**
 * ARCHIVO: documentos/buscar.php
 * BUSQUEDA DE DOCUMENTOS
 */
require_once dirname(__DIR__) . '/config/config.php';
Auth::requireLogin();

$termino = getQuery('q', '');
$filtros = [];
$documentos = [];
$total = 0;

if (!empty($termino) || !empty($_GET)) {
    if (!empty($_GET['anio'])) $filtros['anio'] = getQuery('anio');
    if (!empty($_GET['estado'])) $filtros['estado'] = getQuery('estado');
    if (!empty($_GET['area_emisora_id'])) $filtros['area_emisora_id'] = getQuery('area_emisora_id');
    if (!empty($_GET['tipo_documento_id'])) $filtros['tipo_documento_id'] = getQuery('tipo_documento_id');

    $documentos = Documento::buscar($termino, $filtros, 50, 0);
    $total = Documento::contarBusqueda($termino, $filtros);
}

$areas = Area::findAll();
$tipos = TipoDocumento::findAll();

$pageTitle = 'Buscar Documento';
ob_start();
?>

<div class="card">
    <div class="card-header">
        <h3>Busqueda de Documentos</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <div class="search-box">
                <input type="text" name="q" class="form-control" placeholder="Buscar por codigo, asunto, area, tipo..."
                       value="<?= sanitize($termino) ?>">
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    Buscar
                </button>
            </div>

            <div class="form-row" style="grid-template-columns: 1fr 1fr 1fr 1fr;">
                <div class="form-group">
                    <label>Anio</label>
                    <input type="number" name="anio" class="form-control" placeholder="Ej: 2026"
                           value="<?= sanitize(getQuery('anio')) ?>">
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <select name="estado" class="form-control">
                        <option value="">Todos</option>
                        <option value="disponible" <?= getQuery('estado') === 'disponible' ? 'selected' : '' ?>>Disponible</option>
                        <option value="prestado" <?= getQuery('estado') === 'prestado' ? 'selected' : '' ?>>Prestado</option>
                        <option value="en_revision" <?= getQuery('estado') === 'en_revision' ? 'selected' : '' ?>>En Revision</option>
                        <option value="inactivo" <?= getQuery('estado') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Area Emisora</label>
                    <select name="area_emisora_id" class="form-control">
                        <option value="">Todas</option>
                        <?php foreach ($areas as $area): ?>
                        <option value="<?= $area['id'] ?>" <?= getQuery('area_emisora_id') == $area['id'] ? 'selected' : '' ?>>
                            <?= sanitize($area['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tipo Documento</label>
                    <select name="tipo_documento_id" class="form-control">
                        <option value="">Todos</option>
                        <?php foreach ($tipos as $tipo): ?>
                        <option value="<?= $tipo['id'] ?>" <?= getQuery('tipo_documento_id') == $tipo['id'] ? 'selected' : '' ?>>
                            <?= sanitize($tipo['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($termino) || !empty($_GET['estado']) || !empty($_GET['anio']) || !empty($_GET['area_emisora_id'])): ?>
<div class="card" style="margin-top:16px;">
    <div class="card-header">
        <h3>Resultados (<?= formatNumber($total) ?> documento<?= $total !== 1 ? 's' : '' ?>)</h3>
    </div>
    <div class="card-body" style="padding:0;">
        <?php if (empty($documentos)): ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <p>No se encontraron documentos con los criterios de busqueda.</p>
            </div>
        <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Codigo</th>
                        <th>Anio</th>
                        <th>Area Emisora</th>
                        <th>Tipo</th>
                        <th>Asunto</th>
                        <th>Estado</th>
                        <th>Ubicacion</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documentos as $doc): ?>
                    <tr>
                        <td><strong><?= sanitize($doc['codigo']) ?></strong></td>
                        <td><?= sanitize($doc['anio']) ?></td>
                        <td><?= sanitize($doc['area_emisora_nombre'] ?? '-') ?></td>
                        <td><?= sanitize($doc['tipo_documento_nombre'] ?? '-') ?></td>
                        <td title="<?= sanitize($doc['asunto']) ?>"><?= sanitize(mb_strimwidth($doc['asunto'], 0, 50, '...')) ?></td>
                        <td>
                            <?php
                            $badgeClass = 'badge-default';
                            if ($doc['estado'] === 'disponible') $badgeClass = 'badge-success';
                            elseif ($doc['estado'] === 'prestado') $badgeClass = 'badge-warning';
                            elseif ($doc['estado'] === 'en_revision') $badgeClass = 'badge-info';
                            elseif ($doc['estado'] === 'inactivo') $badgeClass = 'badge-danger';
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= ucfirst($doc['estado']) ?></span>
                        </td>
                        <td>
                            <?php if ($doc['ambiente_nombre']): ?>
                            <div class="location-display">
                                <span class="loc-part"><?= sanitize($doc['ambiente_nombre']) ?></span>
                                <span class="loc-sep">/</span>
                                <span class="loc-part"><?= sanitize($doc['estante_codigo']) ?></span>
                                <span class="loc-sep">/</span>
                                <span class="loc-part">N<?= sanitize($doc['nivel_numero']) ?></span>
                                <span class="loc-sep">/</span>
                                <span class="loc-part">Caja <?= sanitize($doc['caja_numero']) ?></span>
                            </div>
                            <?php else: ?>
                            <span style="color:var(--text-muted);">Sin ubicar</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= SITE_URL ?>/documentos/ver.php?id=<?= $doc['id'] ?>" class="btn btn-sm btn-outline">Ver</a>
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

<?php
$content = ob_get_clean();
require_once PATH_VIEWS . '/layouts/main.php';
