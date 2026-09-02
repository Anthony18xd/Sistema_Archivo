<?php
/**
 * ARCHIVO: models/Tomo.php
 * MODELO DE TOMOS - FASE 1
 * Unidad principal de inventario del archivo municipal.
 */

class Tomo {

    public static function findById(int $id): ?array {
        $stmt = db()->prepare(
            "SELECT t.*,
                    (SELECT COUNT(*) FROM documentos_fase1 df WHERE df.id_tomo = t.id_tomo) AS total_documentos,
                    (SELECT GROUP_CONCAT(DISTINCT de.numero_expediente_unificado SEPARATOR ', ')
                     FROM documento_expedientes de
                     JOIN documentos_fase1 df ON de.id_documento = df.id_documento
                     WHERE df.id_tomo = t.id_tomo) AS expedientes_concat,
                    CASE WHEN EXISTS (
                        SELECT 1 FROM prestamos_fase1 pf
                        WHERE pf.id_tomo = t.id_tomo AND pf.estado = 'activo'
                    ) THEN 1 ELSE 0 END AS esta_prestado
             FROM tomos t
             WHERE t.id_tomo = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function buscar(string $termino, array $filtros = [], int $limit = 50, int $offset = 0): array {
        $conditions = ["t.estado = 'activo'"];
        $params = [];

        if (!empty($termino)) {
            $term = "%{$termino}%";
            $conditions[] = "(
                t.codigo_tomo LIKE :term
                OR t.area LIKE :term2
                OR t.tipo_documento LIKE :term3
                OR EXISTS (
                    SELECT 1 FROM documentos_fase1 df
                    WHERE df.id_tomo = t.id_tomo AND (
                        df.solicitante LIKE :term4
                        OR df.asunto LIKE :term5
                        OR df.expediente_texto LIKE :term6
                    )
                )
                OR EXISTS (
                    SELECT 1 FROM documento_expedientes de
                    JOIN documentos_fase1 df2 ON de.id_documento = df2.id_documento
                    WHERE df2.id_tomo = t.id_tomo AND de.numero_expediente_unificado LIKE :term7
                )
            )";
            $params[':term']  = $term;
            $params[':term2'] = $term;
            $params[':term3'] = $term;
            $params[':term4'] = $term;
            $params[':term5'] = $term;
            $params[':term6'] = $term;
            $params[':term7'] = $term;
        }

        if (!empty($filtros['anio'])) {
            $conditions[] = 't.anio = :anio';
            $params[':anio'] = $filtros['anio'];
        }
        if (!empty($filtros['area'])) {
            $conditions[] = 't.area LIKE :area';
            $params[':area'] = '%' . $filtros['area'] . '%';
        }
        if (!empty($filtros['ubicacion_estado'])) {
            $conditions[] = 't.ubicacion_estado = :ubicacion';
            $params[':ubicacion'] = $filtros['ubicacion_estado'];
        }

        $where = implode(' AND ', $conditions);

        $sql = "SELECT t.id_tomo, t.codigo_tomo, t.anio, t.area, t.tipo_documento,
                       t.cantidad_folios, t.ubicacion_estado, t.estado, t.created_at,
                       (SELECT COUNT(*) FROM documentos_fase1 df WHERE df.id_tomo = t.id_tomo) AS total_documentos,
                       CASE WHEN EXISTS (
                           SELECT 1 FROM prestamos_fase1 pf
                           WHERE pf.id_tomo = t.id_tomo AND pf.estado = 'activo'
                       ) THEN 'prestado' ELSE 'disponible' END AS estado_prestamo
                FROM tomos t
                WHERE {$where}
                ORDER BY t.codigo_tomo ASC
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
        $conditions = ["t.estado = 'activo'"];
        $params = [];

        if (!empty($termino)) {
            $term = "%{$termino}%";
            $conditions[] = "(
                t.codigo_tomo LIKE :term
                OR t.area LIKE :term2
                OR t.tipo_documento LIKE :term3
                OR EXISTS (
                    SELECT 1 FROM documentos_fase1 df
                    WHERE df.id_tomo = t.id_tomo AND (
                        df.solicitante LIKE :term4
                        OR df.asunto LIKE :term5
                        OR df.expediente_texto LIKE :term6
                    )
                )
                OR EXISTS (
                    SELECT 1 FROM documento_expedientes de
                    JOIN documentos_fase1 df2 ON de.id_documento = df2.id_documento
                    WHERE df2.id_tomo = t.id_tomo AND de.numero_expediente_unificado LIKE :term7
                )
            )";
            $params[':term']  = $term;
            $params[':term2'] = $term;
            $params[':term3'] = $term;
            $params[':term4'] = $term;
            $params[':term5'] = $term;
            $params[':term6'] = $term;
            $params[':term7'] = $term;
        }

        if (!empty($filtros['anio'])) {
            $conditions[] = 't.anio = :anio';
            $params[':anio'] = $filtros['anio'];
        }
        if (!empty($filtros['area'])) {
            $conditions[] = 't.area LIKE :area';
            $params[':area'] = '%' . $filtros['area'] . '%';
        }

        $where = implode(' AND ', $conditions);

        $sql = "SELECT COUNT(*)
                FROM tomos t
                WHERE {$where}";

        $stmt = db()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public static function documentros(int $tomoId): array {
        $stmt = db()->prepare(
            "SELECT df.*,
                    (SELECT GROUP_CONCAT(de.numero_expediente_unificado SEPARATOR '|')
                     FROM documento_expedientes de WHERE de.id_documento = df.id_documento) AS expedientes
             FROM documentos_fase1 df
             WHERE df.id_tomo = :id_tomo
             ORDER BY df.solicitante ASC"
        );
        $stmt->execute([':id_tomo' => $tomoId]);
        return $stmt->fetchAll();
    }

    public static function estadisticas(): array {
        $stats = [];

        $stmt = db()->query("SELECT COUNT(*) FROM tomos WHERE estado = 'activo'");
        $stats['total_tomos'] = (int) $stmt->fetchColumn();

        $stmt = db()->query("SELECT COUNT(*) FROM documentos_fase1");
        $stats['total_documentos'] = (int) $stmt->fetchColumn();

        $stmt = db()->query("SELECT COUNT(*) FROM tomos WHERE ubicacion_estado = 'pendiente_asignacion' AND estado = 'activo'");
        $stats['pendientes_asignacion'] = (int) $stmt->fetchColumn();

        $stmt = db()->query(
            "SELECT COUNT(*) FROM tomos t WHERE EXISTS (
                SELECT 1 FROM prestamos_fase1 pf WHERE pf.id_tomo = t.id_tomo AND pf.estado = 'activo'
             )"
        );
        $stats['prestados'] = (int) $stmt->fetchColumn();

        $stmt = db()->query("SELECT COUNT(*) FROM prestamos_fase1 WHERE estado = 'activo'");
        $stats['prestamos_activos'] = (int) $stmt->fetchColumn();

        $stmt = db()->query(
            "SELECT COUNT(*) FROM prestamos_fase1 WHERE estado = 'activo' AND fecha_devolucion < CURDATE()"
        );
        $stats['prestamos_vencidos'] = (int) $stmt->fetchColumn();

        $stmt = db()->query(
            "SELECT anio, COUNT(*) AS total
             FROM tomos WHERE estado = 'activo' AND anio IS NOT NULL
             GROUP BY anio ORDER BY anio DESC LIMIT 10"
        );
        $stats['por_anio'] = $stmt->fetchAll();

        $stmt = db()->query(
            "SELECT area, COUNT(*) AS total
             FROM tomos WHERE estado = 'activo' AND area IS NOT NULL
             GROUP BY area ORDER BY total DESC LIMIT 10"
        );
        $stats['por_area'] = $stmt->fetchAll();

        return $stats;
    }

    public static function existeCodigo(string $codigo, ?int $excludeId = null): bool {
        $sql = "SELECT COUNT(*) FROM tomos WHERE codigo_tomo = :codigo";
        $params = [':codigo' => $codigo];
        if ($excludeId) {
            $sql .= " AND id_tomo != :id";
            $params[':id'] = $excludeId;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Elimina logicamente un tomo (solo accesible a administradores).
     * Marca el tomo y sus documentos como 'inactivo'.
     * Si el tomo tiene un prestamo activo, no se permite eliminar.
     */
    public static function eliminar(int $idTomo): array {
        $tomo = self::findById($idTomo);
        if (!$tomo) {
            return ['ok' => false, 'error' => 'El tomo no existe.'];
        }

        // No permitir eliminar tomos con prestamo activo
        if (PrestamoFase1::estaTomoPrestado($idTomo)) {
            return ['ok' => false, 'error' => 'No se puede eliminar un tomo que tiene un préstamo activo.'];
        }

        $pdo = db();
        $pdo->beginTransaction();

        try {
            // Marcar documentos como inactivos
            $stmt = $pdo->prepare("UPDATE documentos_fase1 SET estado = 'inactivo' WHERE id_tomo = :id_tomo");
            $stmt->execute([':id_tomo' => $idTomo]);

            // Marcar tomo como inactivo
            $stmt = $pdo->prepare(
                "UPDATE tomos SET estado = 'inactivo' WHERE id_tomo = :id_tomo AND estado = 'activo'"
            );
            $stmt->execute([':id_tomo' => $idTomo]);

            if ($stmt->rowCount() === 0) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'El tomo ya se encuentra inactivo o no existe.'];
            }

            $pdo->commit();

            // Auditoria
            if (Auth::check()) {
                Audit::registrar(Auth::id(), 'tomo_eliminacion', 'tomos', $idTomo,
                    "Tomo eliminado: {$tomo['codigo_tomo']}");
            }

            return ['ok' => true];
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Error eliminando tomo: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Error al eliminar el tomo.'];
        }
    }

    public static function areas(): array {
        $stmt = db()->query(
            "SELECT DISTINCT area FROM tomos
             WHERE estado = 'activo' AND area IS NOT NULL AND area != ''
             ORDER BY area ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function anios(): array {
        $stmt = db()->query(
            "SELECT DISTINCT anio FROM tomos
             WHERE estado = 'activo' AND anio IS NOT NULL
             ORDER BY anio DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Crea un tomo manualmente (registro directo en el sistema).
     * Los campos de ubicacion son opcionales; por defecto queda
     * en 'pendiente_asignacion' (Fase 1: sin topografia).
     *
     * @param array    $datos      Datos del tomo
     * @param array    $documentos Lista de documentos asociados
     * @return array               [id_tomo, total_documentos] o lanza excepcion
     */
    public static function create(array $datos, array $documentos = []): int {
        $pdo = db();
        $pdo->beginTransaction();

        try {
            // Ubicacion por defecto para Fase 1
            $ubicacionEstado = $datos['ubicacion_estado'] ?? 'pendiente_asignacion';

            $stmt = $pdo->prepare(
                "INSERT INTO tomos (codigo_tomo, anio, area, tipo_documento, cantidad_folios,
                                    ubicacion_estado, fuente_importacion, usuario_registro_id, observaciones)
                 VALUES (:codigo, :anio, :area, :tipo, :folios, :ubicacion, :fuente, :usuario_id, :observaciones)"
            );
            $stmt->execute([
                ':codigo'        => $datos['codigo_tomo'],
                ':anio'          => !empty($datos['anio']) ? $datos['anio'] : null,
                ':area'          => !empty($datos['area']) ? $datos['area'] : null,
                ':tipo'          => !empty($datos['tipo_documento']) ? $datos['tipo_documento'] : null,
                ':folios'        => !empty($datos['cantidad_folios']) ? $datos['cantidad_folios'] : null,
                ':ubicacion'     => $ubicacionEstado,
                ':fuente'        => 'registro_manual',
                ':usuario_id'    => $datos['usuario_registro_id'] ?? (Auth::check() ? Auth::id() : null),
                ':observaciones' => $datos['observaciones'] ?? null
            ]);

            $idTomo = (int) $pdo->lastInsertId();

            // Registro de documentos asociados (opcional)
            $totalDocs = 0;
            if (!empty($documentos)) {
                foreach ($documentos as $doc) {
                    $solicitante = trim($doc['solicitante'] ?? '');
                    $asunto      = trim($doc['asunto'] ?? '');

                    // Saltar filas vacias
                    if (empty($solicitante) && empty($asunto)) {
                        continue;
                    }

                    $totalDocs += DocumentoFase1::create([
                        'id_tomo'         => $idTomo,
                        'anio'            => !empty($doc['anio']) ? $doc['anio'] : ($datos['anio'] ?? null),
                        'solicitante'     => $solicitante ?: null,
                        'folios_texto'    => !empty($doc['folios_texto']) ? $doc['folios_texto'] : null,
                        'expediente_texto'=> !empty($doc['expediente_texto']) ? $doc['expediente_texto'] : null,
                        'asunto'          => $asunto ?: null
                    ]);
                }
            }

            $pdo->commit();
            return $idTomo;

        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
