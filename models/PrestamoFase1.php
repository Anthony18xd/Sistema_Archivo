<?php
/**
 * ARCHIVO: models/PrestamoFase1.php
 * MODELO DE PRESTAMOS - FASE 1
 * Prestamos simplificados vinculados al TOMO.
 */

class PrestamoFase1 {

    public static function findById(int $id): ?array {
        $stmt = db()->prepare(
            "SELECT pf.*,
                    t.codigo_tomo, t.area AS tomo_area, t.tipo_documento AS tomo_tipo,
                    (SELECT COUNT(*) FROM documentos_fase1 df WHERE df.id_tomo = t.id_tomo) AS total_documentos
             FROM prestamos_fase1 pf
             JOIN tomos t ON pf.id_tomo = t.id_tomo
             WHERE pf.id_prestamo = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function findActivos(): array {
        $stmt = db()->query(
            "SELECT pf.*,
                    t.codigo_tomo, t.area AS tomo_area,
                    (SELECT COUNT(*) FROM documentos_fase1 df WHERE df.id_tomo = t.id_tomo) AS total_documentos
             FROM prestamos_fase1 pf
             JOIN tomos t ON pf.id_tomo = t.id_tomo
             WHERE pf.estado = 'activo'
             ORDER BY pf.fecha_salida DESC"
        );
        return $stmt->fetchAll();
    }

    public static function findVencidos(): array {
        $stmt = db()->query(
            "SELECT pf.*,
                    t.codigo_tomo, t.area AS tomo_area
             FROM prestamos_fase1 pf
             JOIN tomos t ON pf.id_tomo = t.id_tomo
             WHERE pf.estado = 'activo' AND pf.fecha_devolucion < CURDATE()
             ORDER BY pf.fecha_devolucion ASC"
        );
        return $stmt->fetchAll();
    }

    public static function estaTomoPrestado(int $tomoId): bool {
        $stmt = db()->prepare(
            "SELECT COUNT(*) FROM prestamos_fase1
             WHERE id_tomo = :id AND estado = 'activo'"
        );
        $stmt->execute([':id' => $tomoId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function create(array $data): int {
        $stmt = db()->prepare(
            "INSERT INTO prestamos_fase1 (id_tomo, solicitante_prestamo, area_destino,
                                          fecha_salida, fecha_devolucion, estado,
                                          usuario_registro_id, observaciones)
             VALUES (:id_tomo, :solicitante, :area, :salida, :devolucion, 'activo', :usuario, :obs)"
        );
        $stmt->execute([
            ':id_tomo'     => $data['id_tomo'],
            ':solicitante' => $data['solicitante_prestamo'],
            ':area'        => $data['area_destino'] ?? null,
            ':salida'      => $data['fecha_salida'],
            ':devolucion'  => $data['fecha_devolucion'],
            ':usuario'     => $data['usuario_registro_id'] ?? Auth::id(),
            ':obs'         => $data['observaciones'] ?? null
        ]);

        $idPrestamo = (int) db()->lastInsertId();

        // Actualizar estado del tomo
        db()->prepare("UPDATE tomos SET ubicacion_estado = 'prestado' WHERE id_tomo = :id")
            ->execute([':id' => $data['id_tomo']]);

        return $idPrestamo;
    }

    public static function devolver(int $prestamoId, ?int $usuarioId = null): bool {
        $prestamo = self::findById($prestamoId);
        if (!$prestamo || $prestamo['estado'] !== 'activo') return false;

        $stmt = db()->prepare(
            "UPDATE prestamos_fase1
             SET estado = 'devuelto',
                 fecha_devolucion = CURDATE(),
                 usuario_registro_id = COALESCE(:usuario, usuario_registro_id)
             WHERE id = :id AND estado = 'activo'"
        );
        $result = $stmt->execute([
            ':usuario' => $usuarioId ?? Auth::id(),
            ':id'      => $prestamoId
        ]);

        if ($result) {
            // Restablecer estado del tomo
            db()->prepare("UPDATE tomos SET ubicacion_estado = 'asignado' WHERE id_tomo = :id")
                ->execute([':id' => $prestamo['id_tomo']]);
        }

        return $result;
    }

    public static function actualizarEstadoVencidos(): int {
        $stmt = db()->prepare(
            "UPDATE prestamos_fase1 SET estado = 'vencido'
             WHERE estado = 'activo' AND fecha_devolucion < CURDATE()"
        );
        $stmt->execute();
        return $stmt->rowCount();
    }

    public static function historialTomo(int $tomoId): array {
        $stmt = db()->prepare(
            "SELECT pf.*, t.codigo_tomo
             FROM prestamos_fase1 pf
             JOIN tomos t ON pf.id_tomo = t.id_tomo
             WHERE pf.id_tomo = :id_tomo
             ORDER BY pf.fecha_salida DESC"
        );
        $stmt->execute([':id_tomo' => $tomoId]);
        return $stmt->fetchAll();
    }

    public static function estadisticas(): array {
        $stats = [];

        $stmt = db()->query("SELECT COUNT(*) FROM prestamos_fase1 WHERE estado = 'activo'");
        $stats['activos'] = (int) $stmt->fetchColumn();

        $stmt = db()->query("SELECT COUNT(*) FROM prestamos_fase1 WHERE estado = 'vencido'");
        $stats['vencidos'] = (int) $stmt->fetchColumn();

        $stmt = db()->query("SELECT COUNT(*) FROM prestamos_fase1 WHERE estado = 'devuelto'");
        $stats['devueltos'] = (int) $stmt->fetchColumn();

        $stmt = db()->query("SELECT COUNT(*) FROM prestamos_fase1");
        $stats['total'] = (int) $stmt->fetchColumn();

        $stmt = db()->query(
            "SELECT COUNT(*) FROM prestamos_fase1
             WHERE estado = 'activo' AND fecha_devolucion < CURDATE()"
        );
        $stats['vencidos_hoy'] = (int) $stmt->fetchColumn();

        return $stats;
    }

    public static function buscar(array $filtros = [], int $limit = 50, int $offset = 0): array {
        $conditions = [];
        $params = [];

        if (!empty($filtros['estado'])) {
            $conditions[] = 'pf.estado = :estado';
            $params[':estado'] = $filtros['estado'];
        }
        if (!empty($filtros['solicitante'])) {
            $conditions[] = 'pf.solicitante_prestamo LIKE :solicitante';
            $params[':solicitante'] = '%' . $filtros['solicitante'] . '%';
        }
        if (!empty($filtros['fecha_desde'])) {
            $conditions[] = 'pf.fecha_salida >= :fecha_desde';
            $params[':fecha_desde'] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $conditions[] = 'pf.fecha_salida <= :fecha_hasta';
            $params[':fecha_hasta'] = $filtros['fecha_hasta'];
        }

        $whereClause = count($conditions) > 0 ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $sql = "SELECT pf.*,
                       t.codigo_tomo, t.area AS tomo_area
                FROM prestamos_fase1 pf
                JOIN tomos t ON pf.id_tomo = t.id_tomo
                {$whereClause}
                ORDER BY pf.fecha_salida DESC
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
