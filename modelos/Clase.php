<?php
// modelos/Clase.php

class Clase {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    /* ===== Listado y conteo ===== */

    public function contar(string $q=''): int {
        if ($q !== '') {
            $sql = "SELECT COUNT(*) c
                      FROM clases c
                INNER JOIN docentes d    ON d.id = c.docente_id
                INNER JOIN personas pd   ON pd.id = d.persona_id
                INNER JOIN grupos g      ON g.id = c.grupo_id
                LEFT  JOIN asignaturas a ON a.id = c.asignatura_id
                INNER JOIN horarios h     ON h.id = c.horario_id
                     WHERE pd.nombre LIKE :q
                        OR g.seccion LIKE :q
                        OR g.grado LIKE :q
                        OR a.nombre LIKE :q
                        OR c.dia LIKE :q
                        OR h.numero_periodo LIKE :q";
            $st = $this->pdo->prepare($sql);
            $st->execute([':q'=>'%'.$q.'%']);
            return (int)$st->fetchColumn();
        }
        return (int)$this->pdo->query("SELECT COUNT(*) FROM clases")->fetchColumn();
    }

    public function listar(string $q='', int $limit=10, int $offset=0): array {
        $params = [];
        $where = '';
        if ($q !== '') {
            $where = " WHERE pd.nombre LIKE :q OR g.seccion LIKE :q OR g.grado LIKE :q
                       OR a.nombre LIKE :q OR c.dia LIKE :q OR h.numero_periodo LIKE :q ";
            $params[':q'] = '%'.$q.'%';
        }
        $sql = "SELECT c.id, c.docente_id, c.grupo_id, c.asignatura_id, c.dia,
                       c.horario_id, c.hora_inicio, c.hora_fin, c.aula,
                       pd.nombre AS docente,
                       CONCAT(g.grado,' ',g.seccion,' (',g.anio_lectivo,')') AS grupo,
                       a.nombre AS asignatura,
                       h.numero_periodo, h.hora_inicio AS h_ini, h.hora_fin AS h_fin
                  FROM clases c
            INNER JOIN docentes d    ON d.id = c.docente_id
            INNER JOIN personas pd   ON pd.id = d.persona_id
            INNER JOIN grupos g      ON g.id = c.grupo_id
             LEFT JOIN asignaturas a ON a.id = c.asignatura_id
            INNER JOIN horarios h     ON h.id = c.horario_id
                $where
              ORDER BY FIELD(c.dia,'Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'),
                       h.numero_periodo, c.id
                 LIMIT :limit OFFSET :offset";
        $st = $this->pdo->prepare($sql);
        foreach ($params as $k=>$v) $st->bindValue($k,$v, PDO::PARAM_STR);
        $st->bindValue(':limit',$limit,PDO::PARAM_INT);
        $st->bindValue(':offset',$offset,PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ===== CRUD ===== */

    public function obtener(int $id): ?array {
        $st = $this->pdo->prepare("SELECT * FROM clases WHERE id = ?");
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function crear(int $docente_id, int $grupo_id, ?int $asignatura_id, string $dia, int $horario_id, ?string $aula): bool {
        $sql = "INSERT INTO clases (docente_id, grupo_id, asignatura_id, dia, horario_id, aula)
                VALUES (:did,:gid,:aid,:dia,:hid,:aula)";
        $st = $this->pdo->prepare($sql);
        $ok = $st->execute([
            ':did'=>$docente_id, ':gid'=>$grupo_id, ':aid'=>$asignatura_id,
            ':dia'=>$dia, ':hid'=>$horario_id, ':aula'=>$aula
        ]);

        // Compat: si tu tabla tiene hora_inicio/hora_fin, autollenarlas con horarios
        if ($ok && $this->tieneColumnasHoras()) {
            $hid = $horario_id;
            $h = $this->pdo->prepare("SELECT hora_inicio, hora_fin FROM horarios WHERE id=?");
            $h->execute([$hid]);
            if ($row = $h->fetch(PDO::FETCH_ASSOC)) {
                $id = (int)$this->pdo->lastInsertId();
                $upd = $this->pdo->prepare("UPDATE clases SET hora_inicio=:hi, hora_fin=:hf WHERE id=:id");
                $upd->execute([':hi'=>$row['hora_inicio'], ':hf'=>$row['hora_fin'], ':id'=>$id]);
            }
        }
        return $ok;
    }

    public function actualizar(int $id, int $docente_id, int $grupo_id, ?int $asignatura_id, string $dia, int $horario_id, ?string $aula): bool {
        $sql = "UPDATE clases
                   SET docente_id=:did, grupo_id=:gid, asignatura_id=:aid, dia=:dia, horario_id=:hid, aula=:aula
                 WHERE id=:id";
        $st = $this->pdo->prepare($sql);
        $ok = $st->execute([
            ':id'=>$id, ':did'=>$docente_id, ':gid'=>$grupo_id, ':aid'=>$asignatura_id,
            ':dia'=>$dia, ':hid'=>$horario_id, ':aula'=>$aula
        ]);

        if ($ok && $this->tieneColumnasHoras()) {
            $h = $this->pdo->prepare("SELECT hora_inicio, hora_fin FROM horarios WHERE id=?");
            $h->execute([$horario_id]);
            if ($row = $h->fetch(PDO::FETCH_ASSOC)) {
                $upd = $this->pdo->prepare("UPDATE clases SET hora_inicio=:hi, hora_fin=:hf WHERE id=:id");
                $upd->execute([':hi'=>$row['hora_inicio'], ':hf'=>$row['hora_fin'], ':id'=>$id]);
            }
        }
        return $ok;
    }

    public function eliminar(int $id): bool {
        $st = $this->pdo->prepare("DELETE FROM clases WHERE id = ?");
        return $st->execute([$id]);
    }

    /* ===== Utilidades ===== */
    public function hayChoque(?int $id, string $dia, int $horario_id, int $docente_id, int $grupo_id, ?string $aula): bool {
        $sql = "SELECT COUNT(*) c
                  FROM clases
                 WHERE dia = :dia AND horario_id = :hid
                   AND (docente_id = :did OR grupo_id = :gid OR (:aula IS NOT NULL AND aula = :aula))";
        if ($id !== null) $sql .= " AND id <> :id";
        $st = $this->pdo->prepare($sql);
        $params = [':dia'=>$dia, ':hid'=>$horario_id, ':did'=>$docente_id, ':gid'=>$grupo_id, ':aula'=>$aula];
        if ($id !== null) $params[':id'] = $id;
        $st->execute($params);
        return ((int)$st->fetchColumn()) > 0;
    }

    private function tieneColumnasHoras(): bool {
        static $cache = null;
        if ($cache !== null) return $cache;
        $cache = false;
        $q = $this->pdo->query("SHOW COLUMNS FROM clases LIKE 'hora_inicio'");
        if ($q && $q->fetch()) $cache = true;
        return $cache;
    }
}
