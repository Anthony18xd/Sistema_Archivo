<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/css/login.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <svg viewBox="0 0 64 64" width="64" height="64" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="8" y="4" width="48" height="56" rx="4" stroke="currentColor" stroke-width="3" fill="none"/>
                        <rect x="16" y="12" width="32" height="6" rx="2" fill="currentColor" opacity="0.3"/>
                        <rect x="16" y="22" width="32" height="3" rx="1.5" fill="currentColor" opacity="0.2"/>
                        <rect x="16" y="28" width="24" height="3" rx="1.5" fill="currentColor" opacity="0.2"/>
                        <rect x="16" y="34" width="28" height="3" rx="1.5" fill="currentColor" opacity="0.2"/>
                        <rect x="16" y="42" width="32" height="6" rx="2" fill="currentColor" opacity="0.3"/>
                        <rect x="16" y="52" width="20" height="3" rx="1.5" fill="currentColor" opacity="0.2"/>
                    </svg>
                </div>
                <h1><?= sanitize(SITE_NAME) ?></h1>
                <p class="login-subtitle">Sistema Integral de Gestión y Control<br>de Archivo Físico Municipal</p>
            </div>

            <?php $flash = getFlash(); if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= sanitize($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="" class="login-form" autocomplete="off">
                <?= csrfField() ?>

                <div class="form-group">
                    <label for="username">Usuario</label>
                    <input type="text" id="username" name="username" required
                           placeholder="Ingrese su usuario"
                           value="<?= sanitize(getPost('username')) ?>"
                           autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required
                           placeholder="Ingrese su contraseña">
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                        <polyline points="10 17 15 12 10 7"/>
                        <line x1="15" y1="12" x2="3" y2="12"/>
                    </svg>
                    Iniciar Sesión
                </button>
            </form>

            <div class="login-footer">
                <p>Municipalidad Provincial</p>
                <p class="version">v<?= SITE_VERSION ?></p>
            </div>
        </div>
    </div>
</body>
</html>
