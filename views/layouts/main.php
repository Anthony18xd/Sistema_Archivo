<?php
/**
 * ARCHIVO: views/layouts/main.php
 * LAYOUT PRINCIPAL DEL SISTEMA
 */
if (!Auth::check()) {
    redirect(SITE_URL . '/auth/login.php');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Inicio' ?> - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/css/style.css">
</head>
<body>
    <div class="app-layout">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <svg viewBox="0 0 64 64" width="32" height="32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="8" y="4" width="48" height="56" rx="4" stroke="currentColor" stroke-width="3" fill="none"/>
                        <rect x="16" y="12" width="32" height="6" rx="2" fill="currentColor" opacity="0.3"/>
                        <rect x="16" y="22" width="32" height="3" rx="1.5" fill="currentColor" opacity="0.2"/>
                        <rect x="16" y="28" width="24" height="3" rx="1.5" fill="currentColor" opacity="0.2"/>
                        <rect x="16" y="42" width="32" height="6" rx="2" fill="currentColor" opacity="0.3"/>
                    </svg>
                </div>
                <div class="sidebar-title">
                    <strong>ARCHIVO</strong>
                    <small>MUNICIPAL</small>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="<?= SITE_URL ?>/index.php" class="nav-item <?= basename($_SERVER['SCRIPT_NAME']) === 'index.php' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                        <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                    </svg>
                    <span>Dashboard</span>
                </a>

                <a href="<?= SITE_URL ?>/documentos/buscar.php" class="nav-item <?= basename($_SERVER['SCRIPT_NAME']) === 'buscar.php' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <span>Buscar Documento</span>
                </a>

                <?php if (Auth::canWrite()): ?>
                <a href="<?= SITE_URL ?>/documentos/registrar.php" class="nav-item <?= basename($_SERVER['SCRIPT_NAME']) === 'registrar.php' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="12" y1="18" x2="12" y2="12"/>
                        <line x1="9" y1="15" x2="15" y2="15"/>
                    </svg>
                    <span>Registrar Documento</span>
                </a>

                <a href="<?= SITE_URL ?>/documentos/listar.php" class="nav-item <?= basename($_SERVER['SCRIPT_NAME']) === 'listar.php' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/>
                        <line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/>
                        <line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
                    </svg>
                    <span>Inventario</span>
                </a>

                <div class="nav-section">PRESTAMOS</div>

                <a href="<?= SITE_URL ?>/prestamos/registrar.php" class="nav-item <?= basename($_SERVER['SCRIPT_NAME']) === 'registrar.php' && strpos($_SERVER['SCRIPT_NAME'], 'prestamos') !== false ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 11 12 14 22 4"/>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                    </svg>
                    <span>Registrar Prestamo</span>
                </a>

                <a href="<?= SITE_URL ?>/prestamos/devolver.php" class="nav-item <?= basename($_SERVER['SCRIPT_NAME']) === 'devolver.php' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="1 4 1 10 7 10"/>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                    </svg>
                    <span>Devolver Documento</span>
                </a>

                <a href="<?= SITE_URL ?>/prestamos/listar.php" class="nav-item <?= basename($_SERVER['SCRIPT_NAME']) === 'listar.php' && strpos($_SERVER['SCRIPT_NAME'], 'prestamos') !== false ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <span>Historial Prestamos</span>
                </a>
                <?php endif; ?>

                <div class="nav-section">UBICACIONES</div>

                <a href="<?= SITE_URL ?>/ubicaciones/consulta.php" class="nav-item <?= basename($_SERVER['SCRIPT_NAME']) === 'consulta.php' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    <span>Ubicaciones</span>
                </a>

                <?php if (Auth::isAdmin()): ?>
                <div class="nav-section">ADMINISTRACION</div>

                <a href="<?= SITE_URL ?>/usuarios/listar.php" class="nav-item <?= basename($_SERVER['SCRIPT_NAME']) === 'listar.php' && strpos($_SERVER['SCRIPT_NAME'], 'usuarios') !== false ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    <span>Usuarios</span>
                </a>

                <a href="<?= SITE_URL ?>/ubicaciones/administrar.php" class="nav-item <?= basename($_SERVER['SCRIPT_NAME']) === 'administrar.php' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                    <span>Gestionar Ubicaciones</span>
                </a>

                <a href="<?= SITE_URL ?>/reportes/index.php" class="nav-item <?= basename($_SERVER['SCRIPT_NAME']) === 'index.php' && strpos($_SERVER['SCRIPT_NAME'], 'reportes') !== false ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/>
                        <line x1="6" y1="20" x2="6" y2="14"/>
                    </svg>
                    <span>Reportes</span>
                </a>

                <a href="<?= SITE_URL ?>/auditoria/index.php" class="nav-item <?= basename($_SERVER['SCRIPT_NAME']) === 'index.php' && strpos($_SERVER['SCRIPT_NAME'], 'auditoria') !== false ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    <span>Auditoria</span>
                </a>
                <?php endif; ?>
            </nav>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <button class="menu-toggle" id="menuToggle">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="12" x2="21" y2="12"/>
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>

                <div class="topbar-title">
                    <h2><?= $pageTitle ?? 'Inicio' ?></h2>
                </div>

                <div class="topbar-user">
                    <div class="user-info">
                        <span class="user-name"><?= sanitize(Auth::fullName()) ?></span>
                        <span class="user-role"><?= sanitize(Auth::rol()) ?></span>
                    </div>
                    <a href="<?= SITE_URL ?>/auth/logout.php" class="btn-logout" title="Cerrar sesion">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                    </a>
                </div>
            </header>

            <div class="content-wrapper">
                <?php $flash = getFlash(); if ($flash): ?>
                    <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
                <?php endif; ?>

                <?= $content ?>
            </div>
        </main>
    </div>

    <script>
    document.getElementById('menuToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('sidebar-open');
    });
    document.addEventListener('click', function(e) {
        var sidebar = document.getElementById('sidebar');
        var toggle = document.getElementById('menuToggle');
        if (sidebar.classList.contains('sidebar-open') && !sidebar.contains(e.target) && e.target !== toggle) {
            sidebar.classList.remove('sidebar-open');
        }
    });
    </script>
</body>
</html>
