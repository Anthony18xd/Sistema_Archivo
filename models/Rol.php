<?php
/**
 * ARCHIVO: models/Rol.php
 * MODELO DE ROLES
 */

class Rol {

    public static function findAll(): array {
        return db()->query("SELECT * FROM roles ORDER BY nombre")->fetchAll();
    }

    public static function findById(int $id): ?array {
        $stmt = db()->prepare("SELECT * FROM roles WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
}
