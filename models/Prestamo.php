<?php
/**
 * ARCHIVO: models/Prestamo.php
 * MODELO DE PRESTAMOS
 */

class Prestamo {

    public static function findById(int $id): ?array {
        $stmt = db()->prepare(
            "SELECT p.*,
                    d.codigo AS documento_codigo,
                    d.asunto AS documento_asunto,
                    CONCAT(ups.nombres, ' ', ups.apellidos) AS usuario_prestamo_nombre,
                    CONCAT(ud.nombres, ' ', ud.apellidos) AS usuario_devolucion_nombre
             FROM prestamos p
             JOIN documentos d ON p.documento_id = d.id
             JOIN usuarios ups ON p.usuario_prestamo_id = ups.id
             LEFT JOIN usuarios ud ON p.usuario_devolucion_id = ud.id
             WHERE p.id = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function findActivos(): array {
        $stmt = db()->query(
            "SELECT p.*,
                    d.codigo AS documento_codigo,
                    d.asunto AS documento_asunto,
                    CONCAT(ups.nombres, ' ', ups.apellidos) AS usuario_prestamo_nombre
             FROM prestamos p
             JOIN documentos d ON p.documento_id = d.id
             JOIN usuarios ups ON p.usuario_prestamo_id = ups.id
             WHERE p.estado = 'activo'
             ORDER BY p.fecha_salida DESC"
        );
        return $stmt->fetchAll();
    }

    public static function findVencidos(): array {
        $stmt = db()->query(
            "SELECT p.*,
                    d.codigo AS documento_codigo,
                    d.asunto AS documento_asunto,
                    CONCAT(ups.nombres, ' ', ups.apellidos) AS usuario_prestamo_nombre
             FROM prestamos p
             JOIN documentos d ON p.documento_id = d.id
             JOIN usuarios ups ON p.usuario_prestamo_id = ups.id
             WHERE p.estado = 'activo' AND p.fecha_devolucion_estimada < CURDATE()
             ORDER BY p.fecha_devolucion_estimada ASC"
        );
        return $stmt->fetchAll();
    }

    public static function create(array $data): int {
        $stmt = db()->prepare(
            "INSERT INTO prestamos (documento_id, solicitante_nombre, solicitante_dni, solicitante_area,
                                    motivo, fecha_salida, hora_salida, fecha_devolucion_estimada,
                                    usuario_prestamo_id, estado_documento_salida, observaciones, estado)
             VALUES (:documento_id, :solicitante_nombre, :solicitante_dni, :solicitante_area,
                     :motivo, :fecha_salida, :hora_salida, :fecha_devolucion_estimada,
                     :usuario_prestamo_id, :estado_documento_salida, :observaciones, 'activo')"
        );
        $stmt->execute([
            ':documento_id'             => $data['documento_id'],
            ':solicitante_nombre'       => $data['solicitante_nombre'],
            ':solicitante_dni'          => $data['solicitante_dni'] ?? null,
            ':solicitante_area'         => $data['solicitante_area'] ?? null,
            ':motivo'                   => $data['motivo'] ?? null,
            ':fecha_salida'             => $data['fecha_salida'],
            ':hora_salida'              => $data['hora_salida'],
            ':fecha_devolucion_estimada'=> $data['fecha_devolucion_estimada'],
            ':usuario_prestamo_id'      => $data['usuario_prestamo_id'],
            ':estado_documento_salida'  => $data['estado_documento_salida'] ?? null,
            ':observaciones'            => $data['observaciones'] ?? null
        ]);
        return (int) db()->lastInsertId();
    }

    public static function devolver(int $prestamoId, array $data): bool {
        $prestamo = self::findById($prestamoId);
        if (!$prestamo || $prestamo['estado'] !== 'activo') return false;

        $stmt = db()->prepare(
            "UPDATE prestamos
             SET estado = 'devuelto',
                 fecha_devolucion_real = :fecha_devolucion_real,
                 hora_devolucion_real = :hora_devolucion_real,
                 usuario_devolucion_id = :usuario_devolucion_id,
                 estado_documento_entrada = :estado_documento_entrada,
                 observaciones = CONCAT(COALESCE(observaciones, ''), ' | Devolucion: ', :obs_devolucion)
             WHERE id = :id AND estado = 'activo'"
        );
        $result = $stmt->execute([
            ':fecha_devolucion_real'     => $data['fecha_devolucion_real'],
            ':hora_devolucion_real'      => $data['hora_devolucion_real'],
            ':usuario_devolucion_id'     => $data['usuario_devolucion_id'],
            ':estado_documento_entrada'  => $data['estado_documento_entrada'] ?? null,
            ':obs_devolucion'            => $data['observaciones'] ?? '',
            ':id'                        => $prestamoId
        ]);

        return $result;
    }

    public static function actualizarEstadoVencidos(): int {
        $stmt = db()->prepare(
            "UPDATE prestamos SET estado = 'vencido'
             WHERE estado = 'activo' AND fecha_devolucion_estimada < CURDATE()"
        );
        $stmt->execute();
        return $stmt->rowCount();
    }

    public static function historialDocumento(int $documentoId): array {
        $stmt = db()->prepare(
            "SELECT p.*,
                    CONCAT(ups.nombres, ' ', ups.apellidos) AS usuario_prestamo_nombre,
                    CONCAT(ud.nombres, ' ', ud.apellidos) AS usuario_devolucion_nombre
             FROM prestamos p
             JOIN usuarios ups ON p.usuario_prestamo_id = ups.id
             LEFT JOIN usuarios ud ON p.usuario_devolucion_id = ud.id
             WHERE p.documento_id = :documento_id
             ORDER BY p.fecha_salida DESC"
        );
        $stmt->execute([':documento_id' => $documentoId]);
        return $stmt->fetchAll();
    }

    public static function estadisticas(): array {
        $stats = [];

        $stmt = db()->query("SELECT COUNT(*) FROM prestamos WHERE estado = 'activo'");
        $stats['activos'] = (int) $stmt->fetchColumn();

        $stmt = db()->query("SELECT COUNT(*) FROM prestamos WHERE estado = 'vencido'");
        $stats['vencidos'] = (int) $stmt->fetchColumn();

        $stmt = db()->query("SELECT COUNT(*) FROM prestamos WHERE estado = 'devuelto'");
        $stats['devueltos'] = (int) $stmt->fetchColumn();

        $stmt = db()->query("SELECT COUNT(*) FROM prestamos");
        $stats['total'] = (int) $stmt->fetchColumn();

        $stmt = db()->query(
            "SELECT COUNT(*) FROM prestamos
             WHERE estado = 'activo' AND fecha_devolucion_estimada < CURDATE()"
        );
        $stats['vencidos_hoy'] = (int) $stmt->fetchColumn();

        return $stats;
    }

    public static function estaDocumentoPrestado(int $documentoId): bool {
        $stmt = db()->prepare(
            "SELECT COUNT(*) FROM prestamos WHERE documento_id = :id AND estado IN ('activo', 'vencido')"
        );
        $stmt->execute([':id' => $documentoId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function contar(): int {
        $stmt = db()->query("SELECT COUNT(*) FROM prestamos");
        return (int) $stmt->fetchColumn();
    }

    public static function buscar(array $filtros = [], int $limit = 50, int $offset = 0): array {
        $conditions = [];
        $params = [];

        if (!empty($filtros['estado'])) {
            $conditions[] = 'p.estado = :estado';
            $params[':estado'] = $filtros['estado'];
        }
        if (!empty($filtros['documento_id'])) {
            $conditions[] = 'p.documento_id = :documento_id';
            $params[':documento_id'] = $filtros['documento_id'];
        }
        if (!empty($filtros['solicitante_dni'])) {
            $conditions[] = 'p.solicitante_dni = :dni';
            $params[':dni'] = $filtros['solicitante_dni'];
        }
        if (!empty($filtros['fecha_desde'])) {
            $conditions[] = 'p.fecha_salida >= :fecha_desde';
            $params[':fecha_desde'] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $conditions[] = 'p.fecha_salida <= :fecha_hasta';
            $params[':fecha_hasta'] = $filtros['fecha_hasta'];
        }

        $whereClause = count($conditions) > 0 ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $sql = "SELECT p.*,
                       d.codigo AS documento_codigo,
                       d.asunto AS documento_asunto,
                       CONCAT(ups.nombres, ' ', ups.apellidos) AS usuario_prestamo_nombre,
                       CONCAT(ud.nombres, ' ', ud.apellidos) AS usuario_devolucion_nombre
                FROM prestamos p
                JOIN documentos d ON p.documento_id = d.id
                JOIN usuarios ups ON p.usuario_prestamo_id = ups.id
                LEFT JOIN usuarios ud ON p.usuario_devolucion_id = ud.id
                {$whereClause}
                ORDER BY p.fecha_salida DESC
                LIMIT :limit OFFSET :offset";

        $stmt = db()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
