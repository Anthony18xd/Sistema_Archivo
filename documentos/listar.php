<?php
/**
 * ARCHIVO: documentos/listar.php
 * LISTADO DE DOCUMENTOS (INVENTARIO)
 */
require_once dirname(__DIR__) . '/config/config.php';
Auth::requireLogin();

$page = max(1, (int) getQuery('page', 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$filtros = [];
if (!empty($_GET['estado'])) $filtros['estado'] = getQuery('estado');
if (!empty($_GET['anio'])) $filtros['anio'] = getQuery('anio');

$termino = getQuery('q', '');
$documentos = Documento::buscar($termino, $filtros, $perPage, $offset);
$total = Documento::contarBusqueda($termino, $filtros);
$totalPages = max(1, (int) ceil($total / $perPage));

$pageTitle = 'Inventario de Documentos';
ob_start();
?>

<div class="card">
    <div class="card-header">
        <h3>Inventario de Documentos (<?= formatNumber($total) ?> total)</h3>
        <?php if (Auth::canWrite()): ?>
        <a href="<?= SITE_URL ?>/documentos/registrar.php" class="btn btn-sm btn-primary">+ Nuevo Documento</a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <div class="search-box">
                <input type="text" name="q" class="form-control" placeholder="Buscar..."
                       value="<?= sanitize($termino) ?>">
                <select name="estado" class="form-control" style="max-width:180px;">
                    <option value="">Todos los estados</option>
                    <option value="disponible" <?= getQuery('estado') === 'disponible' ? 'selected' : '' ?>>Disponible</option>
                    <option value="prestado" <?= getQuery('estado') === 'prestado' ? 'selected' : '' ?>>Prestado</option>
                    <option value="en_revision" <?= getQuery('estado') === 'en_revision' ? 'selected' : '' ?>>En Revision</option>
                    <option value="inactivo" <?= getQuery('estado') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                </select>
                <input type="number" name="anio" class="form-control" placeholder="Anio" style="max-width:100px;"
                       value="<?= sanitize(getQuery('anio')) ?>">
                <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
            </div>
        </form>
    </div>
    <div class="card-body" style="padding:0;">
        <?php if (empty($documentos)): ?>
            <div class="empty-state">
                <p>No hay documentos registrados.</p>
            </div>
        <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Codigo</th>
                        <th>Anio</th>
                        <th>Area</th>
                        <th>Tipo</th>
                        <th>Asunto</th>
                        <th>Folios</th>
                        <th>Estado</th>
                        <th>Ubicacion</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documentos as $doc): ?>
                    <tr>
                        <td><strong><?= sanitize($doc['codigo']) ?></strong></td>
                        <td><?= sanitize($doc['anio']) ?></td>
                        <td><?= sanitize($doc['area_emisora_nombre'] ?? '-') ?></td>
                        <td><?= sanitize($doc['tipo_documento_nombre'] ?? '-') ?></td>
                        <td title="<?= sanitize($doc['asunto']) ?>"><?= sanitize(mb_strimwidth($doc['asunto'], 0, 45, '...')) ?></td>
                        <td><?= $doc['num_folios'] ? formatNumber((int)$doc['num_folios']) : '-' ?></td>
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
                            <span style="font-size:11px;"><?= sanitize($doc['estante_codigo']) ?> / N<?= sanitize($doc['nivel_numero']) ?> / C<?= sanitize($doc['caja_numero']) ?></span>
                            <?php else: ?>
                            <span style="color:var(--text-muted);">-</span>
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

        <?php if ($totalPages > 1): ?>
        <div style="padding:16px; display:flex; justify-content:center; gap:6px;">
            <?php for ($i = max(1, $page - 3); $i <= min($totalPages, $page + 3); $i++): ?>
            <a href="?page=<?= $i ?>&q=<?= urlencode($termino) ?>&estado=<?= urlencode(getQuery('estado')) ?>&anio=<?= urlencode(getQuery('anio')) ?>"
               class="btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-outline' ?>">
                <?= $i ?>
            </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once PATH_VIEWS . '/layouts/main.php';
