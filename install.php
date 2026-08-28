<?php
/**
 * ARCHIVO: install.php
 * SCRIPT DE INSTALACION DEL SISTEMA
 * Ejecutar una sola vez para crear la base de datos y datos iniciales.
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Instalación - Sistema de Archivo Municipal</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f2f5; display: flex; justify-content: center; padding: 40px; }
        .card { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 24px rgba(0,0,0,0.12); max-width: 600px; width: 100%; }
        h1 { color: #1a3a5c; font-size: 20px; margin-bottom: 20px; }
        .success { color: #059669; font-weight: 600; }
        .error { color: #dc2626; font-weight: 600; }
        pre { background: #f8fafc; padding: 12px; border-radius: 4px; font-size: 12px; overflow-x: auto; border: 1px solid #e5e7eb; }
        .btn { display: inline-block; padding: 10px 20px; background: #1a3a5c; color: #fff; border: none; border-radius: 6px; font-size: 14px; cursor: pointer; text-decoration: none; margin-top: 16px; }
        .btn:hover { background: #0f2440; }
        .warn { background: #fffbeb; border: 1px solid #fde68a; padding: 12px; border-radius: 6px; margin-bottom: 16px; font-size: 13px; color: #92400e; }
    </style>
</head>
<body>
<div class="card">
    <h1>Sistema de Archivo Municipal - Instalación</h1>

    <?php
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $dbName = 'archivo_municipal';

    if (isset($_GET['run'])) {
        echo '<pre>';

        // 1. Crear base de datos
        echo "=== Creando base de datos ===\n";
        try {
            $pdo = new PDO("mysql:host={$host}", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "Base de datos '{$dbName}' creada/verificada.\n";
            $pdo->exec("USE `{$dbName}`");
        } catch (PDOException $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
            echo '</pre></div></body></html>';
            exit;
        }

        // 2. Ejecutar schema
        echo "\n=== Creando tablas ===\n";
        $schemaFile = __DIR__ . '/database/schema.sql';
        if (file_exists($schemaFile)) {
            $sql = file_get_contents($schemaFile);
            $pdo->exec($sql);
            echo "Tablas creadas correctamente.\n";
        } else {
            echo "ERROR: No se encontro database/schema.sql\n";
        }

        // 3. Ejecutar seed
        echo "\n=== Insertando datos iniciales ===\n";
        $seedFile = __DIR__ . '/database/seed.sql';
        if (file_exists($seedFile)) {
            $sql = file_get_contents($seedFile);
            $pdo->exec($sql);
            echo "Datos iniciales insertados.\n";
        } else {
            echo "ERROR: No se encontro database/seed.sql\n";
        }

        echo "\n=== INSTALACION COMPLETADA ===\n";
        echo "\nPuede acceder al sistema con:\n";
        echo "  Usuario: admin\n";
        echo "  Contrasena: admin123\n";
        echo "\nIMPORTANTE: Elimine este archivo (install.php) después de la instalación.\n";
        echo '</pre>';
        echo '<p class="success">Instalación completada exitosamente.</p>';
        echo '<a href="auth/login.php" class="btn">Ir al Login</a>';

    } else {
    ?>

    <div class="warn">
        <strong>Advertencia:</strong> Este script creará la base de datos e insertará datos de prueba.
        Ejecutar una sola vez. Eliminar este archivo después de la instalación.
    </div>

    <p>Base de datos: <strong><?= $dbName ?></strong></p>
    <p>Servidor MySQL: <strong><?= $host ?></strong></p>
    <p>Usuario MySQL: <strong><?= $user ?></strong></p>

    <a href="?run=1" class="btn" onclick="return confirm('¿Desea proceder con la instalación?')">
        Ejecutar Instalación
    </a>

    <?php } ?>
</div>
</body>
</html>
