<?php
/**
 * ARCHIVO: models/TipoDocumento.php
 * MODELO DE TIPOS DE DOCUMENTO
 */

class TipoDocumento {

    public static function findAll(): array {
        return db()->query("SELECT * FROM tipos_documento WHERE estado = 1 ORDER BY nombre")->fetchAll();
    }

    public static function findById(int $id): ?array {
        $stmt = db()->prepare("SELECT * FROM tipos_documento WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function create(array $data): int {
        $stmt = db()->prepare("INSERT INTO tipos_documento (nombre, descripcion) VALUES (:nombre, :descripcion)");
        $stmt->execute([':nombre' => $data['nombre'], ':descripcion' => $data['descripcion'] ?? null]);
        return (int) db()->lastInsertId();
    }

    public static function update(int $id, array $data): bool {
        $stmt = db()->prepare("UPDATE tipos_documento SET nombre = :nombre, descripcion = :descripcion WHERE id = :id");
        return $stmt->execute([':nombre' => $data['nombre'], ':descripcion' => $data['descripcion'] ?? null, ':id' => $id]);
    }

    public static function delete(int $id): bool {
        $stmt = db()->prepare("UPDATE tipos_documento SET estado = 0 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public static function existeNombre(string $nombre, ?int $excludeId = null): bool {
        $sql = "SELECT COUNT(*) FROM tipos_documento WHERE nombre = :nombre";
        $params = [':nombre' => $nombre];
        if ($excludeId) {
            $sql .= " AND id != :id";
            $params[':id'] = $excludeId;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }
}
