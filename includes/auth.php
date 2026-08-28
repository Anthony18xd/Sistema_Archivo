<?php
/**
 * ARCHIVO: includes/auth.php
 * SISTEMA DE AUTENTICACION Y AUTORIZACION
 */

class Auth {

    public static function login(string $username, string $password): ?array {
        $stmt = db()->prepare(
            "SELECT u.*, r.nombre AS rol_nombre, r.permisos
             FROM usuarios u
             JOIN roles r ON u.rol_id = r.id
             WHERE u.username = :username AND u.estado = 1
             LIMIT 1"
        );
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            db()->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id")
                ->execute([':id' => $user['id']]);

            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nombres'] = $user['nombres'];
            $_SESSION['user_apellidos'] = $user['apellidos'];
            $_SESSION['user_username'] = $user['username'];
            $_SESSION['user_rol_id'] = $user['rol_id'];
            $_SESSION['user_rol_nombre'] = $user['rol_nombre'];
            $_SESSION['user_permisos'] = json_decode($user['permisos'], true);
            $_SESSION['logged_in'] = true;

            Audit::registrar($user['id'], 'inicio_sesion', null, null, 'Inicio de sesion exitoso');

            return $user;
        }

        return null;
    }

    public static function logout(): void {
        if (self::check()) {
            Audit::registrar(
                self::id(),
                'cierre_sesion',
                null,
                null,
                'Cierre de sesion'
            );
        }
        session_destroy();
        session_start();
    }

    public static function check(): bool {
        return !empty($_SESSION['logged_in']) && !empty($_SESSION['user_id']);
    }

    public static function id(): int {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    public static function username(): string {
        return $_SESSION['user_username'] ?? '';
    }

    public static function fullName(): string {
        $n = $_SESSION['user_nombres'] ?? '';
        $a = $_SESSION['user_apellidos'] ?? '';
        return trim($n . ' ' . $a);
    }

    public static function rol(): string {
        return $_SESSION['user_rol_nombre'] ?? '';
    }

    public static function rolId(): int {
        return (int) ($_SESSION['user_rol_id'] ?? 0);
    }

    public static function permisos(): array {
        return $_SESSION['user_permisos'] ?? [];
    }

    public static function isAdmin(): bool {
        return self::rolId() === 1;
    }

    public static function isArchivista(): bool {
        return in_array(self::rolId(), [1, 2]);
    }

    public static function canWrite(): bool {
        return self::isAdmin() || self::isArchivista();
    }

    public static function canRead(): bool {
        return self::check();
    }

    public static function requireLogin(): void {
        if (!self::check()) {
            flash('warning', 'Debe iniciar sesion para acceder.');
            redirect(SITE_URL . '/auth/login.php');
        }
    }

    public static function requireAdmin(): void {
        self::requireLogin();
        if (!self::isAdmin()) {
            flash('danger', 'No tiene permisos para acceder a esta seccion.');
            redirect(SITE_URL . '/index.php');
        }
    }

    public static function requireWrite(): void {
        self::requireLogin();
        if (!self::canWrite()) {
            flash('danger', 'No tiene permisos para realizar esta accion.');
            redirect(SITE_URL . '/index.php');
        }
    }
}
