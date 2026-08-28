<?php
/**
 * ARCHIVO: models/Historial.php
 * MODELO DE HISTORIAL DE DOCUMENTOS
 */

class Historial {

    public static function registrar(
        int $documentoId,
        int $usuarioId,
        string $accion,
        ?string $descripcion = null,
        ?array $valoresAnteriores = null,
        ?array $valoresNuevos = null
    ): void {
        $stmt = db()->prepare(
            "INSERT INTO historial_documento (documento_id, usuario_id, accion, descripcion, valores_anteriores, valores_nuevos)
             VALUES (:documento_id, :usuario_id, :accion, :descripcion, :anteriores, :nuevos)"
        );
        $stmt->execute([
            ':documento_id'  => $documentoId,
            ':usuario_id'    => $usuarioId,
            ':accion'        => $accion,
            ':descripcion'   => $descripcion,
            ':anteriores'    => $valoresAnteriores ? json_encode($valoresAnteriores, JSON_UNESCAPED_UNICODE) : null,
            ':nuevos'        => $valoresNuevos ? json_encode($valoresNuevos, JSON_UNESCAPED_UNICODE) : null
        ]);
    }

    public static function findByDocumento(int $documentoId): array {
        $stmt = db()->prepare(
            "SELECT h.*,
                    CONCAT(u.nombres, ' ', u.apellidos) AS usuario_nombre,
                    u.username
             FROM historial_documento h
             JOIN usuarios u ON h.usuario_id = u.id
             WHERE h.documento_id = :documento_id
             ORDER BY h.created_at DESC"
        );
        $stmt->execute([':documento_id' => $documentoId]);
        return $stmt->fetchAll();
    }

    public static function ultimas(int $limit = 20): array {
        $stmt = db()->prepare(
            "SELECT h.*,
                    CONCAT(u.nombres, ' ', u.apellidos) AS usuario_nombre,
                    d.codigo AS documento_codigo,
                    d.asunto AS documento_asunto
             FROM historial_documento h
             JOIN usuarios u ON h.usuario_id = u.id
             JOIN documentos d ON h.documento_id = d.id
             ORDER BY h.created_at DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
