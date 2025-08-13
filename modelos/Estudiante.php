<?php
// modelos/Estudiante.php

class Estudiante {
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function existeNIE(string $nie): bool {
        $stmt = $this->pdo->prepare('SELECT id FROM estudiantes WHERE NIE = :nie LIMIT 1');
        $stmt->execute(['nie' => $nie]);
        return (bool) $stmt->fetch();
    }

    /** Cuenta estudiantes (con búsqueda opcional por nombre o NIE). */
    public function contar(string $q = ''): int {
        if ($q !== '') {
            $like = '%' . $q . '%';
            $sql = "SELECT COUNT(*) AS c
                      FROM estudiantes e
                      JOIN personas p ON p.id = e.persona_id
                     WHERE p.nombre LIKE :q OR e.NIE LIKE :q";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['q' => $like]);
        } else {
            $stmt = $this->pdo->query("SELECT COUNT(*) AS c FROM estudiantes");
        }
        $row = $stmt->fetch();
        return (int)($row['c'] ?? 0);
    }

    /**
     * Lista estudiantes con búsqueda opcional y paginación.
     * @return array<int, array{ id:int, NIE:string, estado:string, nombre:string, fecha_nacimiento:?string, telefono:?string, correo:?string }>
     */
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

        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    /** Crea persona y estudiante en una transacción. Retorna ID o null si falla. */
    public function crearPersonaYEstudiante(array $d): ?int {
        try {
            $this->pdo->beginTransaction();

            // personas
            $sqlP = 'INSERT INTO personas (nombre, fecha_nacimiento, telefono, correo, direccion)
                     VALUES (:nombre, :fecha_nacimiento, :telefono, :correo, :direccion)';
            $stmtP = $this->pdo->prepare($sqlP);
            $stmtP->execute([
                'nombre'           => $d['nombre'],
                'fecha_nacimiento' => $d['fecha_nacimiento'] ?: null,
                'telefono'         => $d['telefono'] ?: null,
                'correo'           => $d['correo'] ?: null,
                'direccion'        => $d['direccion'] ?: null,
            ]);
            $personaId = (int)$this->pdo->lastInsertId();

            // estudiantes
            $sqlE = 'INSERT INTO estudiantes (persona_id, NIE, estado)
                     VALUES (:persona_id, :NIE, :estado)';
            $stmtE = $this->pdo->prepare($sqlE);
            $stmtE->execute([
                'persona_id' => $personaId,
                'NIE'        => $d['NIE'],
                'estado'     => $d['estado'] ?: 'activo',
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
}
