<?php
/**
 * ARCHIVO: models/Documento.php
 * MODELO DE DOCUMENTOS
 */

class Documento {

    public static function findById(int $id): ?array {
        $stmt = db()->prepare(
            "SELECT d.*,
                    ae.nombre AS area_emisora_nombre,
                    ac.nombre AS area_custodio_nombre,
                    td.nombre AS tipo_documento_nombre,
                    c.numero AS caja_numero,
                    n.numero AS nivel_numero,
                    e.codigo AS estante_codigo,
                    e.nombre AS estante_nombre,
                    amb.nombre AS ambiente_nombre,
                    u.username AS usuario_registro_username,
                    CONCAT(u.nombres, ' ', u.apellidos) AS usuario_registro_nombre
             FROM documentos d
             LEFT JOIN areas ae ON d.area_emisora_id = ae.id
             LEFT JOIN areas ac ON d.area_custodio_id = ac.id
             LEFT JOIN tipos_documento td ON d.tipo_documento_id = td.id
             LEFT JOIN cajas c ON d.caja_id = c.id
             LEFT JOIN niveles n ON c.nivel_id = n.id
             LEFT JOIN estantes e ON n.estante_id = e.id
             LEFT JOIN ambientes amb ON e.ambiente_id = amb.id
             JOIN usuarios u ON d.usuario_registro_id = u.id
             WHERE d.id = :id AND d.deleted_at IS NULL
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function findByCodigo(string $codigo): ?array {
        $stmt = db()->prepare(
            "SELECT d.*,
                    ae.nombre AS area_emisora_nombre,
                    ac.nombre AS area_custodio_nombre,
                    td.nombre AS tipo_documento_nombre,
                    c.numero AS caja_numero,
                    n.numero AS nivel_numero,
                    e.codigo AS estante_codigo,
                    e.nombre AS estante_nombre,
                    amb.nombre AS ambiente_nombre
             FROM documentos d
             LEFT JOIN areas ae ON d.area_emisora_id = ae.id
             LEFT JOIN areas ac ON d.area_custodio_id = ac.id
             LEFT JOIN tipos_documento td ON d.tipo_documento_id = td.id
             LEFT JOIN cajas c ON d.caja_id = c.id
             LEFT JOIN niveles n ON c.nivel_id = n.id
             LEFT JOIN estantes e ON n.estante_id = e.id
             LEFT JOIN ambientes amb ON e.ambiente_id = amb.id
             WHERE d.codigo = :codigo AND d.deleted_at IS NULL
             LIMIT 1"
        );
        $stmt->execute([':codigo' => $codigo]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function buscar(string $termino, array $filtros = [], int $limit = 50, int $offset = 0): array {
        $conditions = ["d.deleted_at IS NULL"];
        $params = [];

        if (!empty($termino)) {
            $conditions[] = "(d.codigo LIKE :term OR d.asunto LIKE :term2 OR d.descripcion LIKE :term3
                             OR ae.nombre LIKE :term4 OR td.nombre LIKE :term5 OR d.anio LIKE :term6)";
            $term = "%{$termino}%";
            $params[':term']   = $term;
            $params[':term2']  = $term;
            $params[':term3']  = $term;
            $params[':term4']  = $term;
            $params[':term5']  = $term;
            $params[':term6']  = $term;
        }

        if (!empty($filtros['anio'])) {
            $conditions[] = 'd.anio = :anio';
            $params[':anio'] = $filtros['anio'];
        }
        if (!empty($filtros['area_emisora_id'])) {
            $conditions[] = 'd.area_emisora_id = :area_emisora_id';
            $params[':area_emisora_id'] = $filtros['area_emisora_id'];
        }
        if (!empty($filtros['area_custodio_id'])) {
            $conditions[] = 'd.area_custodio_id = :area_custodio_id';
            $params[':area_custodio_id'] = $filtros['area_custodio_id'];
        }
        if (!empty($filtros['tipo_documento_id'])) {
            $conditions[] = 'd.tipo_documento_id = :tipo_documento_id';
            $params[':tipo_documento_id'] = $filtros['tipo_documento_id'];
        }
        if (!empty($filtros['estado'])) {
            $conditions[] = 'd.estado = :estado';
            $params[':estado'] = $filtros['estado'];
        }
        if (!empty($filtros['estante_id'])) {
            $conditions[] = 'n.estante_id = :estante_id';
            $params[':estante_id'] = $filtros['estante_id'];
        }

        $where = implode(' AND ', $conditions);

        $sql = "SELECT d.id, d.codigo, d.anio, d.asunto, d.estado, d.num_folios, d.fecha_registro,
                       ae.nombre AS area_emisora_nombre,
                       ac.nombre AS area_custodio_nombre,
                       td.nombre AS tipo_documento_nombre,
                       c.numero AS caja_numero,
                       n.numero AS nivel_numero,
                       e.codigo AS estante_codigo,
                       e.nombre AS estante_nombre,
                       amb.nombre AS ambiente_nombre
                FROM documentos d
                LEFT JOIN areas ae ON d.area_emisora_id = ae.id
                LEFT JOIN areas ac ON d.area_custodio_id = ac.id
                LEFT JOIN tipos_documento td ON d.tipo_documento_id = td.id
                LEFT JOIN cajas c ON d.caja_id = c.id
                LEFT JOIN niveles n ON c.nivel_id = n.id
                LEFT JOIN estantes e ON n.estante_id = e.id
                LEFT JOIN ambientes amb ON e.ambiente_id = amb.id
                WHERE {$where}
                ORDER BY d.codigo ASC
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

    public static function contarBusqueda(string $termino, array $filtros = []): int {
        $conditions = ["d.deleted_at IS NULL"];
        $params = [];

        if (!empty($termino)) {
            $conditions[] = "(d.codigo LIKE :term OR d.asunto LIKE :term2 OR d.descripcion LIKE :term3
                             OR ae.nombre LIKE :term4 OR td.nombre LIKE :term5 OR d.anio LIKE :term6)";
            $term = "%{$termino}%";
            $params[':term']   = $term;
            $params[':term2']  = $term;
            $params[':term3']  = $term;
            $params[':term4']  = $term;
            $params[':term5']  = $term;
            $params[':term6']  = $term;
        }

        if (!empty($filtros['anio'])) {
            $conditions[] = 'd.anio = :anio';
            $params[':anio'] = $filtros['anio'];
        }
        if (!empty($filtros['area_emisora_id'])) {
            $conditions[] = 'd.area_emisora_id = :area_emisora_id';
            $params[':area_emisora_id'] = $filtros['area_emisora_id'];
        }
        if (!empty($filtros['estado'])) {
            $conditions[] = 'd.estado = :estado';
            $params[':estado'] = $filtros['estado'];
        }

        $where = implode(' AND ', $conditions);

        $sql = "SELECT COUNT(*)
                FROM documentos d
                LEFT JOIN areas ae ON d.area_emisora_id = ae.id
                LEFT JOIN tipos_documento td ON d.tipo_documento_id = td.id
                LEFT JOIN cajas c ON d.caja_id = c.id
                LEFT JOIN niveles n ON c.nivel_id = n.id
                WHERE {$where}";

        $stmt = db()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public static function create(array $data): int {
        $stmt = db()->prepare(
            "INSERT INTO documentos (codigo, anio, area_emisora_id, area_custodio_id, tipo_documento_id,
                                     caja_id, num_folios, asunto, descripcion, observaciones, estado,
                                     fecha_registro, usuario_registro_id)
             VALUES (:codigo, :anio, :area_emisora_id, :area_custodio_id, :tipo_documento_id,
                     :caja_id, :num_folios, :asunto, :descripcion, :observaciones, :estado,
                     :fecha_registro, :usuario_registro_id)"
        );
        $stmt->execute([
            ':codigo'               => $data['codigo'],
            ':anio'                 => $data['anio'],
            ':area_emisora_id'      => $data['area_emisora_id'] ?: null,
            ':area_custodio_id'     => $data['area_custodio_id'] ?: null,
            ':tipo_documento_id'    => $data['tipo_documento_id'] ?: null,
            ':caja_id'              => $data['caja_id'] ?: null,
            ':num_folios'           => !empty($data['num_folios']) ? $data['num_folios'] : null,
            ':asunto'               => $data['asunto'],
            ':descripcion'          => $data['descripcion'] ?? null,
            ':observaciones'        => $data['observaciones'] ?? null,
            ':estado'               => $data['estado'] ?? 'disponible',
            ':fecha_registro'       => $data['fecha_registro'] ?? date('Y-m-d'),
            ':usuario_registro_id'  => $data['usuario_registro_id']
        ]);
        return (int) db()->lastInsertId();
    }

    public static function update(int $id, array $data): bool {
        $doc = self::findById($id);
        if (!$doc) return false;

        $fields = [];
        $params = [':id' => $id];

        $allowed = ['codigo', 'anio', 'area_emisora_id', 'area_custodio_id', 'tipo_documento_id',
                     'caja_id', 'num_folios', 'asunto', 'descripcion', 'observaciones', 'estado'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field] ?: null;
            }
        }

        if (empty($fields)) return false;

        $sql = "UPDATE documentos SET " . implode(', ', $fields) . " WHERE id = :id AND deleted_at IS NULL";
        $stmt = db()->prepare($sql);
        $result = $stmt->execute($params);

        if ($result) {
            $nuevos = self::findById($id);
            $camposCambiados = [];
            foreach ($allowed as $campo) {
                if (isset($data[$campo]) && $doc[$campo] != $nuevos[$campo]) {
                    $camposCambiados[$campo] = ['anterior' => $doc[$campo], 'nuevo' => $nuevos[$campo]];
                }
            }
            if (!empty($camposCambiados)) {
                Historial::registrar($id, $data['usuario_id'] ?? Auth::id(), 'modificacion',
                    'Documento modificado', null, $camposCambiados);
            }
        }

        return $result;
    }

    public static function delete(int $id, ?int $userId = null): bool {
        $stmt = db()->prepare("UPDATE documentos SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL");
        $result = $stmt->execute([':id' => $id]);
        if ($result) {
            Historial::registrar($id, $userId ?? Auth::id(), 'eliminacion',
                'Documento eliminado logicamente');
        }
        return $result;
    }

    public static function existeCodigo(string $codigo, ?int $excludeId = null): bool {
        $sql = "SELECT COUNT(*) FROM documentos WHERE codigo = :codigo AND deleted_at IS NULL";
        $params = [':codigo' => $codigo];
        if ($excludeId) {
            $sql .= " AND id != :id";
            $params[':id'] = $excludeId;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function estadisticas(): array {
        $stats = [];

        $stmt = db()->query("SELECT COUNT(*) FROM documentos WHERE deleted_at IS NULL");
        $stats['total'] = (int) $stmt->fetchColumn();

        $stmt = db()->query("SELECT COUNT(*) FROM documentos WHERE estado = 'disponible' AND deleted_at IS NULL");
        $stats['disponibles'] = (int) $stmt->fetchColumn();

        $stmt = db()->query("SELECT COUNT(*) FROM documentos WHERE estado = 'prestado' AND deleted_at IS NULL");
        $stats['prestados'] = (int) $stmt->fetchColumn();

        $stmt = db()->query("SELECT COUNT(*) FROM documentos WHERE estado = 'en_revision' AND deleted_at IS NULL");
        $stats['en_revision'] = (int) $stmt->fetchColumn();

        $stmt = db()->query("SELECT COUNT(*) FROM documentos WHERE estado = 'inactivo' AND deleted_at IS NULL");
        $stats['inactivos'] = (int) $stmt->fetchColumn();

        $stmt = db()->query(
            "SELECT COUNT(*) FROM prestamos WHERE estado = 'activo' AND fecha_devolucion_estimada < CURDATE()"
        );
        $stats['prestamos_vencidos'] = (int) $stmt->fetchColumn();

        $stmt = db()->query(
            "SELECT COUNT(*) FROM documentos WHERE deleted_at IS NULL AND fecha_registro >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
        );
        $stats['recientes_7dias'] = (int) $stmt->fetchColumn();

        $stmt = db()->query(
            "SELECT COUNT(*) FROM documentos WHERE deleted_at IS NULL AND fecha_registro >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
        );
        $stats['recientes_30dias'] = (int) $stmt->fetchColumn();

        $stmt = db()->query("SELECT COUNT(*) FROM areas WHERE estado = 1");
        $stats['total_areas'] = (int) $stmt->fetchColumn();

        $stmt = db()->query("SELECT COUNT(*) FROM ambientes WHERE estado = 1");
        $stats['total_ambientes'] = (int) $stmt->fetchColumn();

        $stmt = db()->query("SELECT COUNT(*) FROM estantes WHERE estado = 1");
        $stats['total_estantes'] = (int) $stmt->fetchColumn();

        $stmt = db()->query("SELECT COUNT(*) FROM cajas WHERE estado = 1");
        $stats['total_cajas'] = (int) $stmt->fetchColumn();

        $stmt = db()->query(
            "SELECT d.anio, COUNT(*) AS total
             FROM documentos d
             WHERE d.deleted_at IS NULL
             GROUP BY d.anio
             ORDER BY d.anio DESC
             LIMIT 10"
        );
        $stats['por_anio'] = $stmt->fetchAll();

        $stmt = db()->query(
            "SELECT a.nombre AS area, COUNT(*) AS total
             FROM documentos d
             JOIN areas a ON d.area_emisora_id = a.id
             WHERE d.deleted_at IS NULL
             GROUP BY a.id, a.nombre
             ORDER BY total DESC
             LIMIT 10"
        );
        $stats['por_area'] = $stmt->fetchAll();

        $stmt = db()->query(
            "SELECT t.nombre AS tipo, COUNT(*) AS total
             FROM documentos d
             JOIN tipos_documento t ON d.tipo_documento_id = t.id
             WHERE d.deleted_at IS NULL
             GROUP BY t.id, t.nombre
             ORDER BY total DESC
             LIMIT 10"
        );
        $stats['por_tipo'] = $stmt->fetchAll();

        return $stats;
    }

    public static function recientes(int $limit = 10): array {
        $stmt = db()->prepare(
            "SELECT d.id, d.codigo, d.anio, d.asunto, d.estado, d.fecha_registro,
                    ae.nombre AS area_emisora_nombre,
                    td.nombre AS tipo_documento_nombre
             FROM documentos d
             LEFT JOIN areas ae ON d.area_emisora_id = ae.id
             LEFT JOIN tipos_documento td ON d.tipo_documento_id = td.id
             WHERE d.deleted_at IS NULL
             ORDER BY d.created_at DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function contarPorEstado(string $estado): int {
        $stmt = db()->prepare(
            "SELECT COUNT(*) FROM documentos WHERE estado = :estado AND deleted_at IS NULL"
        );
        $stmt->execute([':estado' => $estado]);
        return (int) $stmt->fetchColumn();
    }
}
