<?php
/**
 * ARCHIVO: index.php
 * DASHBOARD PRINCIPAL - FASE 1
 * Resumen del estado del archivo municipal
 */
require_once 'config/config.php';
Auth::requireLogin();

// Actualizar estados vencidos
PrestamoFase1::actualizarEstadoVencidos();

$stats = Tomo::estadisticas();
$prestamosStats = PrestamoFase1::estadisticas();
$vencidos = PrestamoFase1::findVencidos();

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
            <h4>Total Tomos</h4>
            <span class="stat-value"><?= formatNumber($stats['total_tomos']) ?></span>
        </div>
    </div>

    <div class="stat-card stat-success">
        <div class="stat-icon icon-success">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </div>
        <div class="stat-info">
            <h4>Documentos</h4>
            <span class="stat-value"><?= formatNumber($stats['total_documentos']) ?></span>
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
            <h4>Prestamos Activos</h4>
            <span class="stat-value"><?= formatNumber($stats['prestamos_activos']) ?></span>
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
            <h4>Pendientes Asignacion</h4>
            <span class="stat-value"><?= formatNumber($stats['pendientes_asignacion']) ?></span>
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
            <h4>Areas Registradas</h4>
            <span class="stat-value"><?= count(Tomo::areas()) ?></span>
        </div>
    </div>
</div>

<?php if ($stats['prestamos_vencidos'] > 0): ?>
<div class="alert alert-danger">
    <strong>Atencion:</strong> Existen <?= $stats['prestamos_vencidos'] ?> prestamo(s) vencido(s) que requieren atencion.
</div>
<?php endif; ?>

<div class="quick-actions">
    <a href="<?= SITE_URL ?>/documentos/buscar.php" class="quick-action">
        <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <strong>Buscar Documento</strong>
    </a>
    <?php if (Auth::isAdmin()): ?>
    <a href="<?= SITE_URL ?>/importar.php" class="quick-action">
        <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="7 10 12 15 17 10"/>
            <line x1="12" y1="15" x2="12" y2="3"/>
        </svg>
        <strong>Importar Excel</strong>
    </a>
    <?php endif; ?>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <h3>Prestamos Activos</h3>
        </div>
        <div class="card-body" style="padding:0;">
            <?php $activos = PrestamoFase1::findActivos(); ?>
            <?php if (empty($activos)): ?>
                <div class="empty-state">
                    <p>No hay prestamos activos.</p>
                </div>
            <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Tomo</th>
                            <th>Solicitante</th>
                            <th>Salida</th>
                            <th>Devolucion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($activos, 0, 8) as $p): ?>
                        <tr>
                            <td><strong><?= sanitize($p['codigo_tomo']) ?></strong></td>
                            <td><?= sanitize(mb_strimwidth($p['solicitante_prestamo'], 0, 30, '...')) ?></td>
                            <td><?= dateFormat($p['fecha_salida']) ?></td>
                            <td>
                                <?php
                                $vencida = ($p['fecha_devolucion'] < date('Y-m-d'));
                                ?>
                                <span style="color:<?= $vencida ? 'var(--danger)' : 'inherit' ?>">
                                    <?= dateFormat($p['fecha_devolucion']) ?>
                                </span>
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
            <h3>Documentos por Area</h3>
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
                            <th>Area</th>
                            <th style="text-align:right;">Tomos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['por_area'] as $area): ?>
                        <tr>
                            <td><?= sanitize($area['area'] ?? 'Sin area') ?></td>
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
            <h3>Tomos por Ano</h3>
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
                            <th>Ano</th>
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

    <div class="card">
        <div class="card-header">
            <h3>Ubicacion de Tomos</h3>
        </div>
        <div class="card-body" style="padding:0;">
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Estado</th>
                            <th style="text-align:right;">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="badge badge-success">Disponible</span></td>
                            <td style="text-align:right; font-weight:600;"><?= formatNumber($stats['total_tomos'] - $stats['prestados'] - $stats['pendientes_asignacion']) ?></td>
                        </tr>
                        <tr>
                            <td><span class="badge badge-warning">Prestado</span></td>
                            <td style="text-align:right; font-weight:600;"><?= formatNumber($stats['prestados']) ?></td>
                        </tr>
                        <tr>
                            <td><span class="badge badge-info">Pendiente Asignacion</span></td>
                            <td style="text-align:right; font-weight:600;"><?= formatNumber($stats['pendientes_asignacion']) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once PATH_VIEWS . '/layouts/main.php';
