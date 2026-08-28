<?php
/**
 * ARCHIVO: index.php
 * DASHBOARD PRINCIPAL DEL SISTEMA
 */
require_once 'config/config.php';
Auth::requireLogin();

$stats = Documento::estadisticas();
$prestamosStats = Prestamo::estadisticas();
$vencidos = Prestamo::findVencidos();
$recientes = Documento::recientes(8);

$pageTitle = 'Dashboard';
ob_start();
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon icon-primary">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
            </svg>
        </div>
        <div class="stat-info">
            <h4>Total Documentos</h4>
            <span class="stat-value"><?= formatNumber($stats['total']) ?></span>
        </div>
    </div>

    <div class="stat-card stat-success">
        <div class="stat-icon icon-success">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </div>
        <div class="stat-info">
            <h4>Disponibles</h4>
            <span class="stat-value"><?= formatNumber($stats['disponibles']) ?></span>
        </div>
    </div>

    <div class="stat-card stat-warning">
        <div class="stat-icon icon-warning">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="9 11 12 14 22 4"/>
                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
            </svg>
        </div>
        <div class="stat-info">
            <h4>Prestados</h4>
            <span class="stat-value"><?= formatNumber($stats['prestados']) ?></span>
        </div>
    </div>

    <div class="stat-card stat-danger">
        <div class="stat-icon icon-danger">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
        </div>
        <div class="stat-info">
            <h4>Prestamos Vencidos</h4>
            <span class="stat-value"><?= formatNumber($stats['prestamos_vencidos']) ?></span>
        </div>
    </div>

    <div class="stat-card stat-info">
        <div class="stat-icon icon-info">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
        </div>
        <div class="stat-info">
            <h4>Registrados (30 dias)</h4>
            <span class="stat-value"><?= formatNumber($stats['recientes_30dias']) ?></span>
        </div>
    </div>

    <div class="stat-card stat-accent">
        <div class="stat-icon icon-accent">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                <circle cx="12" cy="10" r="3"/>
            </svg>
        </div>
        <div class="stat-info">
            <h4>Ubicaciones</h4>
            <span class="stat-value"><?= formatNumber($stats['total_cajas']) ?></span>
        </div>
    </div>
</div>

<?php if ($stats['prestamos_vencidos'] > 0): ?>
<div class="alert alert-danger">
    <strong>Atención:</strong> Existen <?= $stats['prestamos_vencidos'] ?> préstamo(s) vencido(s) que requieren atención.
</div>
<?php endif; ?>

<div class="quick-actions">
    <a href="<?= SITE_URL ?>/documentos/buscar.php" class="quick-action">
        <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <strong>Buscar Documento</strong>
    </a>
    <?php if (Auth::canWrite()): ?>
    <a href="<?= SITE_URL ?>/documentos/registrar.php" class="quick-action">
        <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="12" y1="18" x2="12" y2="12"/>
            <line x1="9" y1="15" x2="15" y2="15"/>
        </svg>
        <strong>Registrar Documento</strong>
    </a>
    <a href="<?= SITE_URL ?>/prestamos/registrar.php" class="quick-action">
        <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="9 11 12 14 22 4"/>
            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
        </svg>
        <strong>Registrar Préstamo</strong>
    </a>
    <a href="<?= SITE_URL ?>/prestamos/devolver.php" class="quick-action">
        <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="1 4 1 10 7 10"/>
            <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
        </svg>
        <strong>Devolver Documento</strong>
    </a>
    <?php endif; ?>
    <a href="<?= SITE_URL ?>/documentos/listar.php" class="quick-action">
        <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/>
            <line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/>
            <line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
        </svg>
        <strong>Inventario</strong>
    </a>
    <?php if (Auth::isAdmin()): ?>
    <a href="<?= SITE_URL ?>/reportes/index.php" class="quick-action">
        <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/>
            <line x1="6" y1="20" x2="6" y2="14"/>
        </svg>
        <strong>Reportes</strong>
    </a>
    <?php endif; ?>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <h3>Documentos Recientes</h3>
            <a href="<?= SITE_URL ?>/documentos/listar.php" class="btn btn-sm btn-outline">Ver todos</a>
        </div>
        <div class="card-body" style="padding:0;">
            <?php if (empty($recientes)): ?>
                <div class="empty-state">
                    <p>No hay documentos registrados aun.</p>
                </div>
            <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Asunto</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recientes as $doc): ?>
                        <tr>
                            <td><strong><?= sanitize($doc['codigo']) ?></strong></td>
                            <td><?= sanitize(mb_strimwidth($doc['asunto'], 0, 60, '...')) ?></td>
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
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Documentos por Área Emisora</h3>
        </div>
        <div class="card-body" style="padding:0;">
            <?php if (empty($stats['por_area'])): ?>
                <div class="empty-state">
                    <p>Sin datos disponibles.</p>
                </div>
            <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Área</th>
                            <th style="text-align:right;">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['por_area'] as $area): ?>
                        <tr>
                            <td><?= sanitize($area['area']) ?></td>
                            <td style="text-align:right; font-weight:600;"><?= formatNumber((int)$area['total']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="grid-2" style="margin-top:20px;">
    <div class="card">
        <div class="card-header">
            <h3>Documentos por Tipo</h3>
        </div>
        <div class="card-body" style="padding:0;">
            <?php if (empty($stats['por_tipo'])): ?>
                <div class="empty-state">
                    <p>Sin datos disponibles.</p>
                </div>
            <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th style="text-align:right;">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['por_tipo'] as $tipo): ?>
                        <tr>
                            <td><?= sanitize($tipo['tipo']) ?></td>
                            <td style="text-align:right; font-weight:600;"><?= formatNumber((int)$tipo['total']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Documentos por Año</h3>
        </div>
        <div class="card-body" style="padding:0;">
            <?php if (empty($stats['por_anio'])): ?>
                <div class="empty-state">
                    <p>Sin datos disponibles.</p>
                </div>
            <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Año</th>
                            <th style="text-align:right;">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['por_anio'] as $anio): ?>
                        <tr>
                            <td><?= sanitize($anio['anio']) ?></td>
                            <td style="text-align:right; font-weight:600;"><?= formatNumber((int)$anio['total']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once PATH_VIEWS . '/layouts/main.php';
