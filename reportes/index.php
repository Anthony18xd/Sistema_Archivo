<?php
/**
 * ARCHIVO: reportes/index.php
 * MODULO DE REPORTES
 */
require_once dirname(__DIR__) . '/config/config.php';
Auth::requireLogin();

$stats = Documento::estadisticas();
$prestamosStats = Prestamo::estadisticas();
$vencidos = Prestamo::findVencidos();

$pageTitle = 'Reportes';
ob_start();
?>

<div class="card">
    <div class="card-header">
        <h3>Reportes del Sistema de Archivo</h3>
    </div>
    <div class="card-body">
        <p style="color:var(--text-light); margin-bottom:20px;">Seleccione un tipo de reporte para generar:</p>

        <div class="quick-actions">
            <a href="?tipo=inventario" class="quick-action">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                </svg>
                <strong>Inventario General</strong>
            </a>
            <a href="?tipo=por_anio" class="quick-action">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                </svg>
                <strong>Por Año</strong>
            </a>
            <a href="?tipo=por_area" class="quick-action">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                </svg>
                <strong>Por Área</strong>
            </a>
            <a href="?tipo=por_tipo" class="quick-action">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/>
                    <line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/>
                </svg>
                <strong>Por Tipo</strong>
            </a>
            <a href="?tipo=prestamos" class="quick-action">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 11 12 14 22 4"/>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                </svg>
                <strong>Préstamos</strong>
            </a>
            <a href="?tipo=vencidos" class="quick-action">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <strong>Préstamos Vencidos</strong>
            </a>
            <a href="?tipo=ubicacion" class="quick-action">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                    <circle cx="12" cy="10" r="3"/>
                </svg>
                <strong>Por Ubicación</strong>
            </a>
        </div>
    </div>
</div>

<?php
$tipo = getQuery('tipo', '');
if ($tipo):
?>

<?php if ($tipo === 'inventario'): ?>
<div class="card" style="margin-top:16px;">
    <div class="card-header">
        <h3>Reporte: Inventario General</h3>
        <button onclick="window.print()" class="btn btn-sm btn-outline">Imprimir</button>
    </div>
    <div class="card-body">
        <div class="stats-grid" style="margin-bottom:16px;">
            <div class="stat-card"><div class="stat-info"><h4>Total</h4><span class="stat-value"><?= formatNumber($stats['total']) ?></span></div></div>
            <div class="stat-card stat-success"><div class="stat-info"><h4>Disponibles</h4><span class="stat-value"><?= formatNumber($stats['disponibles']) ?></span></div></div>
            <div class="stat-card stat-warning"><div class="stat-info"><h4>Prestados</h4><span class="stat-value"><?= formatNumber($stats['prestados']) ?></span></div></div>
            <div class="stat-card stat-danger"><div class="stat-info"><h4>Vencidos</h4><span class="stat-value"><?= formatNumber($stats['prestamos_vencidos']) ?></span></div></div>
        </div>
    </div>
</div>

<?php elseif ($tipo === 'por_anio'): ?>
<div class="card" style="margin-top:16px;">
    <div class="card-header">
        <h3>Reporte: Documentos por Año</h3>
        <button onclick="window.print()" class="btn btn-sm btn-outline">Imprimir</button>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Año</th><th style="text-align:right;">Cantidad</th></tr></thead>
                <tbody>
                <?php foreach ($stats['por_anio'] as $a): ?>
                <tr><td><?= sanitize($a['anio']) ?></td><td style="text-align:right;font-weight:600;"><?= formatNumber((int)$a['total']) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php elseif ($tipo === 'por_area'): ?>
<div class="card" style="margin-top:16px;">
    <div class="card-header">
        <h3>Reporte: Documentos por Área Emisora</h3>
        <button onclick="window.print()" class="btn btn-sm btn-outline">Imprimir</button>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Área</th><th style="text-align:right;">Cantidad</th></tr></thead>
                <tbody>
                <?php foreach ($stats['por_area'] as $a): ?>
                <tr><td><?= sanitize($a['area']) ?></td><td style="text-align:right;font-weight:600;"><?= formatNumber((int)$a['total']) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php elseif ($tipo === 'por_tipo'): ?>
<div class="card" style="margin-top:16px;">
    <div class="card-header">
        <h3>Reporte: Documentos por Tipo</h3>
        <button onclick="window.print()" class="btn btn-sm btn-outline">Imprimir</button>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Tipo</th><th style="text-align:right;">Cantidad</th></tr></thead>
                <tbody>
                <?php foreach ($stats['por_tipo'] as $t): ?>
                <tr><td><?= sanitize($t['tipo']) ?></td><td style="text-align:right;font-weight:600;"><?= formatNumber((int)$t['total']) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php elseif ($tipo === 'vencidos'): ?>
<div class="card" style="margin-top:16px;">
    <div class="card-header">
        <h3>Reporte: Préstamos Vencidos</h3>
        <button onclick="window.print()" class="btn btn-sm btn-outline">Imprimir</button>
    </div>
    <div class="card-body" style="padding:0;">
        <?php if (empty($vencidos)): ?>
        <div class="empty-state"><p>No hay préstamos vencidos.</p></div>
        <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Documento</th><th>Solicitante</th><th>Salida</th><th>Vencimiento</th><th>Días Vencido</th></tr></thead>
                <tbody>
                <?php foreach ($vencidos as $v):
                    $dias = (int) ((time() - strtotime($v['fecha_devolucion_estimada'])) / 86400);
                ?>
                <tr>
                    <td><strong><?= sanitize($v['documento_codigo']) ?></strong></td>
                    <td><?= sanitize($v['solicitante_nombre']) ?></td>
                    <td><?= dateFormat($v['fecha_salida']) ?></td>
                    <td><?= dateFormat($v['fecha_devolucion_estimada']) ?></td>
                    <td><span class="badge badge-danger"><?= $dias ?> días</span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($tipo === 'ubicacion'): ?>
<div class="card" style="margin-top:16px;">
    <div class="card-header">
        <h3>Reporte: Inventario por Ubicación</h3>
        <button onclick="window.print()" class="btn btn-sm btn-outline">Imprimir</button>
    </div>
    <div class="card-body">
        <p style="color:var(--text-light);">
            Total de ambientes: <strong><?= formatNumber($stats['total_ambientes']) ?></strong> |
            Total de estantes: <strong><?= formatNumber($stats['total_estantes']) ?></strong> |
            Total de cajas: <strong><?= formatNumber($stats['total_cajas']) ?></strong>
        </p>
    </div>
</div>

<?php endif; ?>
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once PATH_VIEWS . '/layouts/main.php';
