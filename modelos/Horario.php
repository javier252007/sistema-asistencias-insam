<?php
// models/Horario.php
// para gestionar períodos/horarios (número de período -> hora_inicio/hora_fin)

class Horario
{
    public static function all(PDO $pdo): array
    {
        $stmt = $pdo->query("SELECT id, numero_periodo, hora_inicio, hora_fin
                             FROM horarios
                             ORDER BY numero_periodo");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function disponibles(PDO $pdo, string $dia, ?int $docenteId = null, ?int $grupoId = null, ?string $aula = null): array
    {
        // Devuelve períodos libres para ese día (evita choques con docente/grupo/aula)
        $sql = "SELECT h.*
                FROM horarios h
                WHERE NOT EXISTS (
                    SELECT 1
                    FROM clases c
                    WHERE c.dia = :dia AND c.horario_id = h.id
                      AND (
                           (:docenteId IS NOT NULL AND c.docente_id = :docenteId)
                        OR (:grupoId   IS NOT NULL AND c.grupo_id   = :grupoId)
                        OR (:aula      IS NOT NULL AND c.aula       = :aula)
                      )
                )
                ORDER BY h.numero_periodo";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':dia'       => $dia,
            ':docenteId' => $docenteId,
            ':grupoId'   => $grupoId,
            ':aula'      => $aula
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare("SELECT id, numero_periodo, hora_inicio, hora_fin FROM horarios WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
