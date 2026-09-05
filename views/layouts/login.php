<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - <?= SITE_NAME ?></title>
    <link rel="icon" type="image/png" href="<?= SITE_URL ?>/imagenes/icono.png">
    <link rel="stylesheet" href="<?= SITE_URL ?>/css/login.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <img src="<?= SITE_URL ?>/imagenes/icono.png" alt="Logo Archivo Municipal">
                </div>
                <h1><?= sanitize(SITE_NAME) ?></h1>
                <p class="login-subtitle">Sistema Integral de Gestión y Control<br>de Archivo Físico Municipal</p>
            </div>

            <?php $flash = getFlash(); if ($flash): ?>
                <div class="alert alert-<?= in_array($flash['type'], ['success', 'danger', 'warning', 'info']) ? $flash['type'] : 'info' ?>"><?= sanitize($flash['message']) ?></div>
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
