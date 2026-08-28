<?php
/**
 * ARCHIVO: models/Ubicacion.php
 * MODELO DE UBICACIONES FISICAS
 */

class Ubicacion {

    // ---- AMBIENTES ----
    public static function ambientes(): array {
        return db()->query("SELECT * FROM ambientes WHERE estado = 1 ORDER BY nombre")->fetchAll();
    }

    public static function ambienteById(int $id): ?array {
        $stmt = db()->prepare("SELECT * FROM ambientes WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function crearAmbiente(array $data): int {
        $stmt = db()->prepare("INSERT INTO ambientes (nombre, descripcion) VALUES (:nombre, :descripcion)");
        $stmt->execute([':nombre' => $data['nombre'], ':descripcion' => $data['descripcion'] ?? null]);
        return (int) db()->lastInsertId();
    }

    public static function actualizarAmbiente(int $id, array $data): bool {
        $stmt = db()->prepare("UPDATE ambientes SET nombre = :nombre, descripcion = :descripcion WHERE id = :id");
        return $stmt->execute([':nombre' => $data['nombre'], ':descripcion' => $data['descripcion'] ?? null, ':id' => $id]);
    }

    // ---- ESTANTES ----
    public static function estantes(?int $ambienteId = null): array {
        $sql = "SELECT e.*, a.nombre AS ambiente_nombre
                FROM estantes e
                JOIN ambientes a ON e.ambiente_id = a.id
                WHERE e.estado = 1";
        $params = [];
        if ($ambienteId) {
            $sql .= " AND e.ambiente_id = :ambiente_id";
            $params[':ambiente_id'] = $ambienteId;
        }
        $sql .= " ORDER BY a.nombre, e.codigo";
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function estanteById(int $id): ?array {
        $stmt = db()->prepare(
            "SELECT e.*, a.nombre AS ambiente_nombre
             FROM estantes e JOIN ambientes a ON e.ambiente_id = a.id
             WHERE e.id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function crearEstante(array $data): int {
        $stmt = db()->prepare("INSERT INTO estantes (ambiente_id, codigo, nombre, descripcion) VALUES (:amb, :cod, :nom, :desc)");
        $stmt->execute([
            ':amb'  => $data['ambiente_id'],
            ':cod'  => $data['codigo'],
            ':nom'  => $data['nombre'],
            ':desc' => $data['descripcion'] ?? null
        ]);
        return (int) db()->lastInsertId();
    }

    // ---- NIVELES ----
    public static function niveles(int $estanteId): array {
        $stmt = db()->prepare("SELECT * FROM niveles WHERE estante_id = :estante_id AND estado = 1 ORDER BY numero");
        $stmt->execute([':estante_id' => $estanteId]);
        return $stmt->fetchAll();
    }

    public static function nivelById(int $id): ?array {
        $stmt = db()->prepare(
            "SELECT n.*, e.codigo AS estante_codigo, e.nombre AS estante_nombre, a.nombre AS ambiente_nombre
             FROM niveles n
             JOIN estantes e ON n.estante_id = e.id
             JOIN ambientes a ON e.ambiente_id = a.id
             WHERE n.id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function crearNivel(array $data): int {
        $stmt = db()->prepare("INSERT INTO niveles (estante_id, numero, descripcion) VALUES (:est, :num, :desc)");
        $stmt->execute([
            ':est'  => $data['estante_id'],
            ':num'  => $data['numero'],
            ':desc' => $data['descripcion'] ?? null
        ]);
        return (int) db()->lastInsertId();
    }

    // ---- CAJAS ----
    public static function cajas(int $nivelId): array {
        $stmt = db()->prepare("SELECT * FROM cajas WHERE nivel_id = :nivel_id AND estado = 1 ORDER BY numero");
        $stmt->execute([':nivel_id' => $nivelId]);
        return $stmt->fetchAll();
    }

    public static function cajaById(int $id): ?array {
        $stmt = db()->prepare(
            "SELECT c.*,
                    n.numero AS nivel_numero,
                    e.codigo AS estante_codigo,
                    e.nombre AS estante_nombre,
                    a.nombre AS ambiente_nombre
             FROM cajas c
             JOIN niveles n ON c.nivel_id = n.id
             JOIN estantes e ON n.estante_id = e.id
             JOIN ambientes a ON e.ambiente_id = a.id
             WHERE c.id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function crearCaja(array $data): int {
        $stmt = db()->prepare("INSERT INTO cajas (nivel_id, numero, codigo, descripcion, capacidad) VALUES (:niv, :num, :cod, :desc, :cap)");
        $stmt->execute([
            ':niv' => $data['nivel_id'],
            ':num' => $data['numero'],
            ':cod' => $data['codigo'] ?? null,
            ':desc'=> $data['descripcion'] ?? null,
            ':cap' => $data['capacidad'] ?? null
        ]);
        return (int) db()->lastInsertId();
    }

    public static function todasLasCajas(): array {
        return db()->query(
            "SELECT c.*, n.numero AS nivel_numero, e.codigo AS estante_codigo, a.nombre AS ambiente_nombre
             FROM cajas c
             JOIN niveles n ON c.nivel_id = n.id
             JOIN estantes e ON n.estante_id = e.id
             JOIN ambientes a ON e.ambiente_id = a.id
             WHERE c.estado = 1
             ORDER BY a.nombre, e.codigo, n.numero, c.numero"
        )->fetchAll();
    }

    public static function ubicacionCompleta(int $cajaId): ?array {
        return self::cajaById($cajaId);
    }
}
