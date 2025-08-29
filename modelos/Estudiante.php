<?php
// modelos/Estudiante.php

class Estudiante {
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo) {
        // Sugerencia: forzar modos seguros (en caso no estén globalmente)
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->pdo = $pdo;
    }

    /* =========================
       Listado / Búsqueda / Alta
       ========================= */

    /** Verifica si existe un NIE exacto */
    public function existeNIE(string $nie): bool {
        $stmt = $this->pdo->prepare('SELECT 1 FROM estudiantes WHERE NIE = :nie LIMIT 1');
        $stmt->execute(['nie' => $nie]);
        return (bool)$stmt->fetchColumn();
    }

    /** Cuenta estudiantes (con filtro opcional por nombre o NIE) */
    public function contar(string $q = ''): int {
        if ($q !== '') {
            $like = '%' . $q . '%';
            $sql = "SELECT COUNT(*) AS c
                      FROM estudiantes e
                      JOIN personas p ON p.id = e.persona_id
                     WHERE p.nombre LIKE :q OR e.NIE LIKE :q";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':q', $like, PDO::PARAM_STR);
            $stmt->execute();
        } else {
            $stmt = $this->pdo->query("SELECT COUNT(*) AS c FROM estudiantes");
        }
        $row = $stmt->fetch();
        return (int)($row['c'] ?? 0);
    }

    /** Listado paginado (con filtro opcional por nombre o NIE) */
    public function listar(string $q = '', int $limit = 10, int $offset = 0): array {
        if ($q !== '') {
            $like = '%' . $q . '%';
            $sql = "SELECT e.id, e.NIE, e.estado,
                           p.nombre, p.fecha_nacimiento, p.telefono, p.correo
                      FROM estudiantes e
                      JOIN personas p ON p.id = e.persona_id
                     WHERE p.nombre LIKE :q OR e.NIE LIKE :q
                  ORDER BY p.nombre ASC
                     LIMIT :lim OFFSET :off";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':q', $like, PDO::PARAM_STR);
        } else {
            $sql = "SELECT e.id, e.NIE, e.estado,
                           p.nombre, p.fecha_nacimiento, p.telefono, p.correo
                      FROM estudiantes e
                      JOIN personas p ON p.id = e.persona_id
                  ORDER BY p.nombre ASC
                     LIMIT :lim OFFSET :off";
            $stmt = $this->pdo->prepare($sql);
        }
        // IMPORTANTE: bind de enteros como PARAM_INT
        $stmt->bindValue(':lim', max(0, $limit), PDO::PARAM_INT);
        $stmt->bindValue(':off', max(0, $offset), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Crea persona y estudiante en transacción. Devuelve ID del estudiante o null. */
    public function crearPersonaYEstudiante(array $d): ?int {
        try {
            $this->pdo->beginTransaction();

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

            $sqlE = "INSERT INTO estudiantes (persona_id, NIE, estado)
                     VALUES (:pid, :nie, :estado)";
            $stE = $this->pdo->prepare($sqlE);
            $stE->execute([
                'pid'    => $personaId,
                'nie'    => $d['NIE'],
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

    /** Obtiene un estudiante por ID (incluye datos de persona) */
    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT e.id, e.NIE, e.estado, e.persona_id,
                       p.nombre, p.fecha_nacimiento, p.telefono, p.correo, p.direccion
                  FROM estudiantes e
                  JOIN personas p ON p.id = e.persona_id
                 WHERE e.id = :id";
        $st = $this->pdo->prepare($sql);
        $st->execute(['id' => $id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /** Verifica si un NIE ya está usado por otro estudiante distinto del actual */
    public function nieUsadoPorOtro(string $nie, int $idActual): bool {
        $sql = "SELECT 1 FROM estudiantes WHERE NIE = :nie AND id <> :id LIMIT 1";
        $st  = $this->pdo->prepare($sql);
        $st->execute(['nie' => $nie, 'id' => $idActual]);
        return (bool)$st->fetchColumn();
    }

    /** Actualiza persona + estudiante en transacción */
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

            // Estudiante
            $sqlE = "UPDATE estudiantes
                        SET NIE = :nie,
                            estado = :estado
                      WHERE id = :id";
            $stE = $this->pdo->prepare($sqlE);
            $stE->execute([
                'nie'    => $d['NIE'],
                'estado' => $d['estado'] ?? 'activo',
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

            if (!$row) {
                $this->pdo->rollBack();
                return false;
            }
            $personaId = (int)$row['persona_id'];

            // 2) borrar estudiante
            $this->pdo->prepare("DELETE FROM estudiantes WHERE id = :id")
                      ->execute(['id' => $id]);

            // 3) ver si la persona está en uso en otras tablas
            $queries = [
                "SELECT 1 FROM estudiantes WHERE persona_id = :pid LIMIT 1",
                "SELECT 1 FROM docentes    WHERE persona_id = :pid LIMIT 1",
                "SELECT 1 FROM usuarios    WHERE persona_id = :pid LIMIT 1",
                // En tu esquema actual, incidentes_estudiantes.registrado_por → personas(id)
                "SELECT 1 FROM incidentes_estudiantes WHERE registrado_por = :pid LIMIT 1"
            ];

            $enUso = false;
            foreach ($queries as $q) {
                $s = $this->pdo->prepare($q);
                $s->execute(['pid' => $personaId]);
                if ($s->fetchColumn()) { $enUso = true; break; }
            }

            // 4) si no está en uso, borrar persona
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

    /* ============================
       Métodos para Asistencia (NIE)
       ============================ */

    /**
     * Devuelve una lista (máx. $limit) por prefijo de NIE (para autocompletar).
     * Retorna: [ {id, NIE, estado, nombre} ... ]
     */
    public function findByNiePrefix(string $prefix, int $limit = 10): array {
        // Sanitizar límites
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

    /**
     * Obtiene estudiante por ID (versión ligera para asistencia).
     * Retorna: {id, NIE, estado, nombre, telefono, correo} | null
     */
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

    /**
     * (Opcional) Busca estudiante por NIE exacto.
     * Retorna: {id, NIE, estado, nombre} | null
     */
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
