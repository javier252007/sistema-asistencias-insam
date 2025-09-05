<?php
// modelos/Horario.php

class Horario {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function todos(): array {
        $sql = "SELECT id, numero_periodo, hora_inicio, hora_fin
                FROM horarios
                ORDER BY numero_periodo";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function disponibles(string $dia, ?int $docenteId = null, ?int $grupoId = null, ?string $aula = null): array {
        $sql = "SELECT h.*
                  FROM horarios h
                 WHERE NOT EXISTS (
                        SELECT 1
                          FROM clases c
                         WHERE c.dia = :dia
                           AND c.horario_id = h.id
                           AND (
                                 (:docenteId IS NOT NULL AND c.docente_id = :docenteId)
                              OR (:grupoId   IS NOT NULL AND c.grupo_id   = :grupoId)
                              OR (:aula      IS NOT NULL AND c.aula       = :aula)
                           )
                 )
              ORDER BY h.numero_periodo";
        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':dia'       => $dia,
            ':docenteId' => $docenteId,
            ':grupoId'   => $grupoId,
            ':aula'      => $aula ?: null,
        ]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array {
        $st = $this->pdo->prepare("SELECT * FROM horarios WHERE id = ?");
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
