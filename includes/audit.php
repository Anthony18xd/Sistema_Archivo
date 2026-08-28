<?php
/**
 * ARCHIVO: includes/audit.php
 * SISTEMA DE AUDITORIA
 */

class Audit {

    public static function registrar(
        ?int $usuarioId,
        string $accion,
        ?string $tabla,
        ?int $registroId,
        ?string $detalle = null
    ): void {
        try {
            $stmt = db()->prepare(
                "INSERT INTO auditoria (usuario_id, accion, tabla, registro_id, detalle, ip_address, user_agent)
                 VALUES (:usuario_id, :accion, :tabla, :registro_id, :detalle, :ip, :ua)"
            );
            $stmt->execute([
                ':usuario_id'  => $usuarioId,
                ':accion'      => $accion,
                ':tabla'       => $tabla,
                ':registro_id' => $registroId,
                ':detalle'     => $detalle,
                ':ip'          => getIp(),
                ':ua'          => substr(getUserAgent(), 0, 500)
            ]);
        } catch (PDOException $e) {
            error_log('Error en auditoría: ' . $e->getMessage());
        }
    }

    public static function getRegistros(int $limit = 100, int $offset = 0, array $filtros = []): array {
        $where = [];
        $params = [];

        if (!empty($filtros['usuario_id'])) {
            $where[] = 'a.usuario_id = :usuario_id';
            $params[':usuario_id'] = $filtros['usuario_id'];
        }
        if (!empty($filtros['accion'])) {
            $where[] = 'a.accion = :accion';
            $params[':accion'] = $filtros['accion'];
        }
        if (!empty($filtros['tabla'])) {
            $where[] = 'a.tabla = :tabla';
            $params[':tabla'] = $filtros['tabla'];
        }
        if (!empty($filtros['fecha_desde'])) {
            $where[] = 'a.created_at >= :fecha_desde';
            $params[':fecha_desde'] = $filtros['fecha_desde'] . ' 00:00:00';
        }
        if (!empty($filtros['fecha_hasta'])) {
            $where[] = 'a.created_at <= :fecha_hasta';
            $params[':fecha_hasta'] = $filtros['fecha_hasta'] . ' 23:59:59';
        }

        $whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT a.*, u.username, u.nombres, u.apellidos
                FROM auditoria a
                LEFT JOIN usuarios u ON a.usuario_id = u.id
                {$whereClause}
                ORDER BY a.created_at DESC
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

    public static function contar(array $filtros = []): int {
        $where = [];
        $params = [];

        if (!empty($filtros['usuario_id'])) {
            $where[] = 'a.usuario_id = :usuario_id';
            $params[':usuario_id'] = $filtros['usuario_id'];
        }
        if (!empty($filtros['accion'])) {
            $where[] = 'a.accion = :accion';
            $params[':accion'] = $filtros['accion'];
        }
        if (!empty($filtros['fecha_desde'])) {
            $where[] = 'a.created_at >= :fecha_desde';
            $params[':fecha_desde'] = $filtros['fecha_desde'] . ' 00:00:00';
        }
        if (!empty($filtros['fecha_hasta'])) {
            $where[] = 'a.created_at <= :fecha_hasta';
            $params[':fecha_hasta'] = $filtros['fecha_hasta'] . ' 23:59:59';
        }

        $whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT COUNT(*) FROM auditoria a {$whereClause}";
        $stmt = db()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }
}
