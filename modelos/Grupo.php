<?php
// modelos/Grupo.php

class Grupo {
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /* ==========================
       Utilidades / catálogos
       ========================== */

    public function listarModalidades(): array {
        $sql = "SELECT id, nombre FROM modalidades ORDER BY nombre ASC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarDocentesActivos(): array {
        $sql = "SELECT d.id, p.nombre
                  FROM docentes d
                  JOIN personas p ON p.id = d.persona_id
                 WHERE d.activo = 1
              ORDER BY p.nombre ASC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ==========================
       Listado / Búsqueda / Paginación
       ========================== */

    public function contar(string $q = ''): int {
        if ($q !== '') {
            $like = '%' . $q . '%';
            $sql = "SELECT COUNT(*) AS c
                      FROM grupos g
                 LEFT JOIN modalidades m ON m.id = g.modalidad_id
                 LEFT JOIN docentes d    ON d.id = g.docente_guia_id
                 LEFT JOIN personas p    ON p.id = d.persona_id
                     WHERE g.seccion LIKE :q
                        OR g.grado LIKE :q
                        OR CAST(g.anio_lectivo AS CHAR) LIKE :q
                        OR m.nombre LIKE :q
                        OR p.nombre LIKE :q";
            $st = $this->pdo->prepare($sql);
            $st->bindValue(':q', $like, PDO::PARAM_STR);
            $st->execute();
        } else {
            $st = $this->pdo->query("SELECT COUNT(*) AS c FROM grupos");
        }
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
    }

    public function listar(string $q = '', int $limit = 10, int $offset = 0): array {
        if ($q !== '') {
            $like = '%' . $q . '%';
            $sql = "SELECT g.id, g.seccion, g.grado, g.anio_lectivo,
                           m.nombre AS modalidad,
                           p.nombre AS docente
                      FROM grupos g
                 LEFT JOIN modalidades m ON m.id = g.modalidad_id
                 LEFT JOIN docentes d    ON d.id = g.docente_guia_id
                 LEFT JOIN personas p    ON p.id = d.persona_id
                     WHERE g.seccion LIKE :q
                        OR g.grado LIKE :q
                        OR CAST(g.anio_lectivo AS CHAR) LIKE :q
                        OR m.nombre LIKE :q
                        OR p.nombre LIKE :q
                  ORDER BY g.anio_lectivo DESC, g.grado ASC, g.seccion ASC
                     LIMIT :lim OFFSET :off";
            $st = $this->pdo->prepare($sql);
            $st->bindValue(':q', $like, PDO::PARAM_STR);
        } else {
            $sql = "SELECT g.id, g.seccion, g.grado, g.anio_lectivo,
                           m.nombre AS modalidad,
                           p.nombre AS docente
                      FROM grupos g
                 LEFT JOIN modalidades m ON m.id = g.modalidad_id
                 LEFT JOIN docentes d    ON d.id = g.docente_guia_id
                 LEFT JOIN personas p    ON p.id = d.persona_id
                  ORDER BY g.anio_lectivo DESC, g.grado ASC, g.seccion ASC
                     LIMIT :lim OFFSET :off";
            $st = $this->pdo->prepare($sql);
        }
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ==========================
       CRUD
       ========================== */

    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT g.*,
                       m.nombre AS modalidad_nombre,
                       p.nombre AS docente_nombre
                  FROM grupos g
             LEFT JOIN modalidades m ON m.id = g.modalidad_id
             LEFT JOIN docentes d    ON d.id = g.docente_guia_id
             LEFT JOIN personas p    ON p.id = d.persona_id
                 WHERE g.id = :id";
        $st = $this->pdo->prepare($sql);
        $st->execute(['id' => $id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function crear(array $d): ?int {
        $sql = "INSERT INTO grupos (docente_guia_id, modalidad_id, seccion, grado, anio_lectivo)
                VALUES (:docente, :modalidad, :seccion, :grado, :anio)";
        $st = $this->pdo->prepare($sql);
        $ok = $st->execute([
            'docente'   => $d['docente_guia_id'] ?: null,
            'modalidad' => $d['modalidad_id']    ?: null,
            'seccion'   => $d['seccion'],
            'grado'     => $d['grado'],
            'anio'      => $d['anio_lectivo'],
        ]);
        return $ok ? (int)$this->pdo->lastInsertId() : null;
    }

    public function actualizar(array $d): bool {
        $sql = "UPDATE grupos
                   SET docente_guia_id = :docente,
                       modalidad_id    = :modalidad,
                       seccion         = :seccion,
                       grado           = :grado,
                       anio_lectivo    = :anio
                 WHERE id = :id";
        $st = $this->pdo->prepare($sql);
        return $st->execute([
            'docente'   => $d['docente_guia_id'] ?: null,
            'modalidad' => $d['modalidad_id']    ?: null,
            'seccion'   => $d['seccion'],
            'grado'     => $d['grado'],
            'anio'      => $d['anio_lectivo'],
            'id'        => $d['id'],
        ]);
    }

    public function estaEnUso(int $id): bool {
        // Un grupo está en uso si existe al menos una clase vinculada
        $st = $this->pdo->prepare("SELECT 1 FROM clases WHERE grupo_id = :id LIMIT 1");
        $st->execute(['id' => $id]);
        return (bool)$st->fetchColumn();
    }

    public function eliminar(int $id): bool {
        if ($this->estaEnUso($id)) {
            return false; // proteger integridad si hay clases asociadas
        }
        $st = $this->pdo->prepare("DELETE FROM grupos WHERE id = :id");
        return $st->execute(['id' => $id]);
    }
}
