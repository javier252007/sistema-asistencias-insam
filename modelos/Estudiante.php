<?php
// modelos/Estudiante.php

class Estudiante {
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo) {
        // Modos seguros
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->pdo = $pdo;
    }

    /* =========================
       Listado / Búsqueda / Alta
       ========================= */

    /** Verifica si existe un NIE exacto (ignora NIE vacío) */
    public function existeNIE(string $nie): bool {
        $nie = trim($nie);
        if ($nie === '') return false;
        $stmt = $this->pdo->prepare('SELECT 1 FROM estudiantes WHERE NIE = :nie LIMIT 1');
        $stmt->execute(['nie' => $nie]);
        return (bool)$stmt->fetchColumn();
    }

    /** Cuenta estudiantes (buscador por nombre, NIE, grado, sección) */
    public function contar(string $q = ''): int {
        if ($q !== '') {
            $like = '%' . $q . '%';
            $sql = "SELECT COUNT(*) AS c
                      FROM estudiantes e
                      JOIN personas p ON p.id = e.persona_id
                 LEFT JOIN grupos   g ON g.id = e.grupo_id       -- 👈 LEFT JOIN
                     WHERE p.nombre LIKE :q
                        OR e.NIE    LIKE :q
                        OR g.grado  LIKE :q
                        OR g.seccion LIKE :q";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':q', $like, PDO::PARAM_STR);
            $stmt->execute();
        } else {
            $stmt = $this->pdo->query("SELECT COUNT(*) AS c FROM estudiantes");
        }
        $row = $stmt->fetch();
        return (int)($row['c'] ?? 0);
    }

    /** Listado paginado (incluye estudiantes sin grupo) */
    public function listar(string $q = '', int $limit = 10, int $offset = 0): array {
        if ($q !== '') {
            $like = '%' . $q . '%';
            $sql = "SELECT e.id, e.NIE, e.estado, e.persona_id, e.grupo_id,
                           p.nombre, p.fecha_nacimiento, p.telefono, p.correo,
                           g.grado, g.seccion
                      FROM estudiantes e
                      JOIN personas p ON p.id = e.persona_id
                 LEFT JOIN grupos   g ON g.id = e.grupo_id      -- 👈 LEFT JOIN
                     WHERE p.nombre LIKE :q
                        OR e.NIE    LIKE :q
                        OR g.grado  LIKE :q
                        OR g.seccion LIKE :q
                  ORDER BY (e.grupo_id IS NULL) DESC, p.nombre ASC
                     LIMIT :lim OFFSET :off";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':q', $like, PDO::PARAM_STR);
        } else {
            $sql = "SELECT e.id, e.NIE, e.estado, e.persona_id, e.grupo_id,
                           p.nombre, p.fecha_nacimiento, p.telefono, p.correo,
                           g.grado, g.seccion
                      FROM estudiantes e
                      JOIN personas p ON p.id = e.persona_id
                 LEFT JOIN grupos   g ON g.id = e.grupo_id      -- 👈 LEFT JOIN
                  ORDER BY (e.grupo_id IS NULL) DESC, p.nombre ASC
                     LIMIT :lim OFFSET :off";
            $stmt = $this->pdo->prepare($sql);
        }
        $stmt->bindValue(':lim', max(0, $limit), PDO::PARAM_INT);
        $stmt->bindValue(':off', max(0, $offset), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Crea persona y estudiante en transacción. Devuelve ID del estudiante o null.
     * Requiere: nombre, grupo_id (según tu esquema actual). NIE es opcional.
     * $d keys: nombre, fecha_nacimiento, telefono, correo, direccion, NIE, estado, grupo_id
     */
    public function crearPersonaYEstudiante(array $d): ?int {
        try {
            $this->pdo->beginTransaction();

            // Persona
            $sqlP = "INSERT INTO personas (nombre, fecha_nacimiento, telefono, correo, direccion)
                     VALUES (:nombre, :fnac, :tel, :correo, :dir)";
            $stP = $this->pdo->prepare($sqlP);
            $stP->execute([
                'nombre' => $d['nombre'],
                'fnac'   => $d['fecha_nacimiento'] ?? null,
                'tel'    => $d['telefono'] ?? null,
                'correo' => $d['correo'] ?? null,
                'dir'    => $d['direccion'] ?? null,
            ]);
            $personaId = (int)$this->pdo->lastInsertId();

            // Estudiante
            $sqlE = "INSERT INTO estudiantes (persona_id, NIE, grupo_id, estado)
                     VALUES (:pid, :nie, :gid, :estado)";
            $stE = $this->pdo->prepare($sqlE);
            $stE->execute([
                'pid'    => $personaId,
                'nie'    => (isset($d['NIE']) && trim((string)$d['NIE']) !== '') ? $d['NIE'] : null,
                'gid'    => (int)$d['grupo_id'],
                'estado' => $d['estado'] ?? 'activo',
            ]);
            $estudianteId = (int)$this->pdo->lastInsertId();

            $this->pdo->commit();
            return $estudianteId;

        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            error_log('[Estudiante::crearPersonaYEstudiante] ' . $e->getMessage());
            return null;
        }
    }

    /* =============================
       Soporte para Editar / Borrar
       ============================= */

    /** Obtiene un estudiante por ID (incluye datos de persona y grupo, aunque sea NULL) */
    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT e.id, e.NIE, e.estado, e.persona_id, e.grupo_id,
                       p.nombre, p.fecha_nacimiento, p.telefono, p.correo, p.direccion,
                       g.grado, g.seccion
                  FROM estudiantes e
                  JOIN personas p ON p.id = e.persona_id
             LEFT JOIN grupos   g ON g.id = e.grupo_id          -- 👈 LEFT JOIN
                 WHERE e.id = :id";
        $st = $this->pdo->prepare($sql);
        $st->execute(['id' => $id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /** Verifica si un NIE ya está usado por otro estudiante distinto del actual */
    public function nieUsadoPorOtro(string $nie, int $idActual): bool {
        $nie = trim($nie);
        if ($nie === '') return false;
        $sql = "SELECT 1 FROM estudiantes WHERE NIE = :nie AND id <> :id LIMIT 1";
        $st  = $this->pdo->prepare($sql);
        $st->execute(['nie' => $nie, 'id' => $idActual]);
        return (bool)$st->fetchColumn();
    }

    /** Actualiza persona + estudiante (incluye cambio de grupo) en transacción */
    public function actualizarPersonaYEstudiante(array $d): bool {
        try {
            $this->pdo->beginTransaction();

            // Persona
            $sqlP = "UPDATE personas
                        SET nombre = :nombre,
                            fecha_nacimiento = :fnac,
                            telefono = :tel,
                            correo = :correo,
                            direccion = :dir
                      WHERE id = :pid";
            $stP = $this->pdo->prepare($sqlP);
            $stP->execute([
                'nombre' => $d['nombre'],
                'fnac'   => $d['fecha_nacimiento'] ?? null,
                'tel'    => $d['telefono'] ?? null,
                'correo' => $d['correo'] ?? null,
                'dir'    => $d['direccion'] ?? null,
                'pid'    => $d['persona_id'],
            ]);

            // Estudiante (grupo puede cambiar)
            $sqlE = "UPDATE estudiantes
                        SET NIE = :nie,
                            estado = :estado,
                            grupo_id = :gid
                      WHERE id = :id";
            $stE = $this->pdo->prepare($sqlE);
            $stE->execute([
                'nie'    => (isset($d['NIE']) && trim((string)$d['NIE']) !== '') ? $d['NIE'] : null,
                'estado' => $d['estado'] ?? 'activo',
                'gid'    => (int)$d['grupo_id'],
                'id'     => $d['id'],
            ]);

            $this->pdo->commit();
            return true;

        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            error_log('[Estudiante::actualizarPersonaYEstudiante] ' . $e->getMessage());
            return false;
        }
    }

    /** Elimina estudiante y (si no está en uso) su persona */
    public function eliminar(int $id): bool {
        try {
            $this->pdo->beginTransaction();

            // 1) persona_id
            $st = $this->pdo->prepare("SELECT persona_id FROM estudiantes WHERE id = :id");
            $st->execute(['id' => $id]);
            $row = $st->fetch();
            if (!$row) { $this->pdo->rollBack(); return false; }
            $personaId = (int)$row['persona_id'];

            // 2) borrar estudiante
            $this->pdo->prepare("DELETE FROM estudiantes WHERE id = :id")
                      ->execute(['id' => $id]);

            // 3) verificar uso de la persona
            $queries = [
                "SELECT 1 FROM estudiantes WHERE persona_id = :pid LIMIT 1",
                "SELECT 1 FROM docentes    WHERE persona_id = :pid LIMIT 1",
                "SELECT 1 FROM usuarios    WHERE persona_id = :pid LIMIT 1",
                "SELECT 1 FROM incidentes_estudiantes WHERE registrado_por = :pid LIMIT 1"
            ];
            $enUso = false;
            foreach ($queries as $q) {
                $s = $this->pdo->prepare($q);
                $s->execute(['pid' => $personaId]);
                if ($s->fetchColumn()) { $enUso = true; break; }
            }

            if (!$enUso) {
                $this->pdo->prepare("DELETE FROM personas WHERE id = :pid")
                          ->execute(['pid' => $personaId]);
            }

            $this->pdo->commit();
            return true;

        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            error_log('[Estudiante::eliminar] ' . $e->getMessage());
            return false;
        }
    }

    /* ============== Extra útil: Reasignación rápida ============== */
    public function updateGrupo(int $estudianteId, ?int $nuevoGrupoId): bool {
        $st = $this->pdo->prepare("UPDATE estudiantes SET grupo_id = :gid WHERE id = :id");
        return $st->execute([':gid' => $nuevoGrupoId, ':id' => $estudianteId]);
    }

    /* ============================ Asistencia (NIE) ============================ */

    public function findByNiePrefix(string $prefix, int $limit = 10): array {
        $limit = max(1, min(100, $limit));
        $sql = "SELECT e.id, e.NIE, e.estado, p.nombre
                  FROM estudiantes e
                  JOIN personas p ON p.id = e.persona_id
                 WHERE e.NIE LIKE :pref
              ORDER BY e.NIE ASC
                 LIMIT :lim";
        $stmt = $this->pdo->prepare($sql);
        $like = $prefix . '%';
        $stmt->bindValue(':pref', $like, PDO::PARAM_STR);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array {
        $sql = "SELECT e.id, e.NIE, e.estado,
                       p.nombre, p.telefono, p.correo
                  FROM estudiantes e
                  JOIN personas p ON p.id = e.persona_id
                 WHERE e.id = :id
                 LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function obtenerPorNIE(string $nie): ?array {
        $sql = "SELECT e.id, e.NIE, e.estado, p.nombre
                  FROM estudiantes e
                  JOIN personas p ON p.id = e.persona_id
                 WHERE e.NIE = :nie
                 LIMIT 1";
        $st = $this->pdo->prepare($sql);
        $st->execute([':nie' => $nie]);
        $row = $st->fetch();
        return $row ?: null;
    }
}
