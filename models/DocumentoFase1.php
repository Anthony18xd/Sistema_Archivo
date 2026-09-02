<?php
/**
 * ARCHIVO: models/DocumentoFase1.php
 * MODELO DE DOCUMENTOS - FASE 1
 * Documentos individuales dentro de un tomo.
 * Soporta folios textuales y multiples expedientes.
 */

class DocumentoFase1 {

    public static function findById(int $id): ?array {
        $stmt = db()->prepare(
            "SELECT df.*,
                    t.codigo_tomo, t.area AS tomo_area, t.tipo_documento AS tomo_tipo,
                    (SELECT GROUP_CONCAT(de.numero_expediente_unificado SEPARATOR '|')
                     FROM documento_expedientes de WHERE de.id_documento = df.id_documento) AS expedientes
             FROM documentos_fase1 df
             JOIN tomos t ON df.id_tomo = t.id_tomo
             WHERE df.id_documento = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function findByTomo(int $tomoId): array {
        $stmt = db()->prepare(
            "SELECT df.*,
                    (SELECT GROUP_CONCAT(de.numero_expediente_unificado SEPARATOR '|')
                     FROM documento_expedientes de WHERE de.id_documento = df.id_documento) AS expedientes
             FROM documentos_fase1 df
             WHERE df.id_tomo = :id_tomo
             ORDER BY df.solicitante ASC, df.id_documento ASC"
        );
        $stmt->execute([':id_tomo' => $tomoId]);
        return $stmt->fetchAll();
    }

    public static function buscar(string $termino, array $filtros = [], int $limit = 50, int $offset = 0): array {
        $conditions = ["df.estado = 'activo'"];
        $params = [];

        if (!empty($termino)) {
            $term = "%{$termino}%";
            $conditions[] = "(
                df.solicitante LIKE :term
                OR df.asunto LIKE :term2
                OR df.expediente_texto LIKE :term3
                OR t.codigo_tomo LIKE :term4
                OR EXISTS (
                    SELECT 1 FROM documento_expedientes de
                    WHERE de.id_documento = df.id_documento
                    AND de.numero_expediente_unificado LIKE :term5
                )
            )";
            $params[':term']  = $term;
            $params[':term2'] = $term;
            $params[':term3'] = $term;
            $params[':term4'] = $term;
            $params[':term5'] = $term;
        }

        if (!empty($filtros['anio'])) {
            $conditions[] = 'df.anio = :anio';
            $params[':anio'] = $filtros['anio'];
        }
        if (!empty($filtros['id_tomo'])) {
            $conditions[] = 'df.id_tomo = :id_tomo';
            $params[':id_tomo'] = $filtros['id_tomo'];
        }

        $where = implode(' AND ', $conditions);

        $sql = "SELECT df.id_documento, df.solicitante, df.folios_texto, df.expediente_texto,
                       df.asunto, df.anio, df.hoja_origen, df.fila_origen, df.estado,
                       t.codigo_tomo, t.area AS tomo_area,
                       (SELECT COUNT(*) FROM documento_expedientes de WHERE de.id_documento = df.id_documento) AS num_expedientes
                FROM documentos_fase1 df
                JOIN tomos t ON df.id_tomo = t.id_tomo
                WHERE {$where}
                ORDER BY t.codigo_tomo ASC, df.solicitante ASC
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
        $conditions = ["df.estado = 'activo'"];
        $params = [];

        if (!empty($termino)) {
            $term = "%{$termino}%";
            $conditions[] = "(
                df.solicitante LIKE :term
                OR df.asunto LIKE :term2
                OR df.expediente_texto LIKE :term3
                OR t.codigo_tomo LIKE :term4
                OR EXISTS (
                    SELECT 1 FROM documento_expedientes de
                    WHERE de.id_documento = df.id_documento
                    AND de.numero_expediente_unificado LIKE :term5
                )
            )";
            $params[':term']  = $term;
            $params[':term2'] = $term;
            $params[':term3'] = $term;
            $params[':term4'] = $term;
            $params[':term5'] = $term;
        }

        if (!empty($filtros['anio'])) {
            $conditions[] = 'df.anio = :anio';
            $params[':anio'] = $filtros['anio'];
        }

        $where = implode(' AND ', $conditions);

        $sql = "SELECT COUNT(*)
                FROM documentos_fase1 df
                JOIN tomos t ON df.id_tomo = t.id_tomo
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
            "INSERT INTO documentos_fase1 (id_tomo, anio, solicitante, folios_texto,
                                           expediente_texto, asunto, hoja_origen, fila_origen)
             VALUES (:id_tomo, :anio, :solicitante, :folios, :expediente, :asunto, :hoja, :fila)"
        );
        $stmt->execute([
            ':id_tomo'     => $data['id_tomo'],
            ':anio'        => $data['anio'] ?? null,
            ':solicitante' => $data['solicitante'] ?? null,
            ':folios'      => $data['folios_texto'] ?? null,
            ':expediente'  => $data['expediente_texto'] ?? null,
            ':asunto'      => $data['asunto'] ?? null,
            ':hoja'        => $data['hoja_origen'] ?? null,
            ':fila'        => $data['fila_origen'] ?? null
        ]);
        $idDocumento = (int) db()->lastInsertId();

        // Registrar expedientes individuales
        if (!empty($data['expediente_texto'])) {
            self::registrarExpedientes($idDocumento, $data['expediente_texto']);
        }

        return $idDocumento;
    }

    /**
     * Descompone una cadena de expedientes (1036|1342) y los registra en la tabla puente.
     */
    public static function registrarExpedientes(int $idDocumento, string $expedientesTexto): void {
        $nums = array_filter(array_map('trim', explode('|', $expedientesTexto)));
        if (empty($nums)) return;

        $stmt = db()->prepare(
            "INSERT INTO documento_expedientes (id_documento, numero_expediente_unificado)
             VALUES (:id_doc, :numero)"
        );

        foreach ($nums as $num) {
            if (!empty($num)) {
                $stmt->execute([
                    ':id_doc' => $idDocumento,
                    ':numero' => $num
                ]);
            }
        }
    }

    public static function expedientes(int $idDocumento): array {
        $stmt = db()->prepare(
            "SELECT * FROM documento_expedientes
             WHERE id_documento = :id
             ORDER BY numero_expediente_unificado ASC"
        );
        $stmt->execute([':id' => $idDocumento]);
        return $stmt->fetchAll();
    }

    public static function esPrestado(int $idDocumento): bool {
        $stmt = db()->prepare(
            "SELECT df.id_tomo FROM documentos_fase1 df
             WHERE df.id_documento = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $idDocumento]);
        $tomoId = $stmt->fetchColumn();

        if (!$tomoId) return false;

        $stmt2 = db()->prepare(
            "SELECT COUNT(*) FROM prestamos_fase1
             WHERE id_tomo = :tomo AND estado = 'activo'"
        );
        $stmt2->execute([':tomo' => $tomoId]);
        return (int) $stmt2->fetchColumn() > 0;
    }

    public static function estadisticasPorTomo(int $tomoId): array {
        $stats = [];

        $stmt = db()->prepare("SELECT COUNT(*) FROM documentos_fase1 WHERE id_tomo = :id");
        $stmt->execute([':id' => $tomoId]);
        $stats['total'] = (int) $stmt->fetchColumn();

        $stmt = db()->prepare(
            "SELECT COUNT(DISTINCT de.numero_expediente_unificado)
             FROM documento_expedientes de
             JOIN documentos_fase1 df ON de.id_documento = df.id_documento
             WHERE df.id_tomo = :id"
        );
        $stmt->execute([':id' => $tomoId]);
        $stats['expedientes_unicos'] = (int) $stmt->fetchColumn();

        return $stats;
    }
}
