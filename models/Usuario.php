<?php
/**
 * ARCHIVO: models/Usuario.php
 * MODELO DE USUARIOS
 */

class Usuario {

    public static function findById(int $id): ?array {
        $stmt = db()->prepare(
            "SELECT u.*, r.nombre AS rol_nombre
             FROM usuarios u
             JOIN roles r ON u.rol_id = r.id
             WHERE u.id = :id AND u.deleted_at IS NULL
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function findAll(): array {
        $stmt = db()->query(
            "SELECT u.*, r.nombre AS rol_nombre
             FROM usuarios u
             JOIN roles r ON u.rol_id = r.id
             WHERE u.deleted_at IS NULL
             ORDER BY u.apellidos, u.nombres"
        );
        return $stmt->fetchAll();
    }

    public static function findActive(): array {
        $stmt = db()->query(
            "SELECT u.*, r.nombre AS rol_nombre
             FROM usuarios u
             JOIN roles r ON u.rol_id = r.id
             WHERE u.estado = 1 AND u.deleted_at IS NULL
             ORDER BY u.apellidos, u.nombres"
        );
        return $stmt->fetchAll();
    }

    public static function create(array $data): int {
        $stmt = db()->prepare(
            "INSERT INTO usuarios (rol_id, nombres, apellidos, dni, email, telefono, username, password, estado)
             VALUES (:rol_id, :nombres, :apellidos, :dni, :email, :telefono, :username, :password, :estado)"
        );
        $stmt->execute([
            ':rol_id'    => $data['rol_id'],
            ':nombres'   => $data['nombres'],
            ':apellidos' => $data['apellidos'],
            ':dni'       => $data['dni'] ?? null,
            ':email'     => $data['email'] ?? null,
            ':telefono'  => $data['telefono'] ?? null,
            ':username'  => $data['username'],
            ':password'  => password_hash($data['password'], PASSWORD_DEFAULT),
            ':estado'    => $data['estado'] ?? 1
        ]);
        return (int) db()->lastInsertId();
    }

    public static function update(int $id, array $data): bool {
        $fields = [];
        $params = [':id' => $id];

        $allowed = ['rol_id', 'nombres', 'apellidos', 'dni', 'email', 'telefono', 'username', 'estado'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (!empty($data['password'])) {
            $fields[] = 'password = :password';
            $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (empty($fields)) return false;

        $sql = "UPDATE usuarios SET " . implode(', ', $fields) . " WHERE id = :id AND deleted_at IS NULL";
        $stmt = db()->prepare($sql);
        return $stmt->execute($params);
    }

    public static function delete(int $id): bool {
        $stmt = db()->prepare("UPDATE usuarios SET deleted_at = NOW() WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public static function existsUsername(string $username, ?int $excludeId = null): bool {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE username = :username AND deleted_at IS NULL";
        $params = [':username' => $username];
        if ($excludeId) {
            $sql .= " AND id != :id";
            $params[':id'] = $excludeId;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function existsDni(string $dni, ?int $excludeId = null): bool {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE dni = :dni AND deleted_at IS NULL";
        $params = [':dni' => $dni];
        if ($excludeId) {
            $sql .= " AND id != :id";
            $params[':id'] = $excludeId;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function contar(): int {
        $stmt = db()->query("SELECT COUNT(*) FROM usuarios WHERE deleted_at IS NULL");
        return (int) $stmt->fetchColumn();
    }

    public static function contarActivos(): int {
        $stmt = db()->query("SELECT COUNT(*) FROM usuarios WHERE estado = 1 AND deleted_at IS NULL");
        return (int) $stmt->fetchColumn();
    }
}
