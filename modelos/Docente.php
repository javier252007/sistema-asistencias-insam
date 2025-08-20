<?php
// modelos/Docente.php

class Docente {
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /** Cuenta docentes (con búsqueda opcional por nombre o correo/teléfono) */
    public function contar(string $q = ''): int {
        if ($q !== '') {
            $like = '%'.$q.'%';
            $sql = "SELECT COUNT(*) AS c
                      FROM docentes d
                      JOIN personas p ON p.id = d.persona_id
                     WHERE p.nombre LIKE :q OR p.correo LIKE :q OR p.telefono LIKE :q";
            $st = $this->pdo->prepare($sql);
            $st->execute(['q'=>$like]);
        } else {
            $st = $this->pdo->query("SELECT COUNT(*) AS c FROM docentes");
        }
        $row = $st->fetch();
        return (int)($row['c'] ?? 0);
    }

    /** Lista docentes con paginación y búsqueda */
    public function listar(string $q = '', int $limit = 10, int $offset = 0): array {
        if ($q !== '') {
            $like = '%'.$q.'%';
            $sql = "SELECT d.id, d.activo,
                           p.nombre, p.fecha_nacimiento, p.telefono, p.correo, p.direccion
                      FROM docentes d
                      JOIN personas p ON p.id = d.persona_id
                     WHERE p.nombre LIKE :q OR p.correo LIKE :q OR p.telefono LIKE :q
                  ORDER BY p.nombre ASC
                     LIMIT :lim OFFSET :off";
            $st = $this->pdo->prepare($sql);
            $st->bindValue(':q', $like, PDO::PARAM_STR);
        } else {
            $sql = "SELECT d.id, d.activo,
                           p.nombre, p.fecha_nacimiento, p.telefono, p.correo, p.direccion
                      FROM docentes d
                      JOIN personas p ON p.id = d.persona_id
                  ORDER BY p.nombre ASC
                     LIMIT :lim OFFSET :off";
            $st = $this->pdo->prepare($sql);
        }
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll() ?: [];
    }

    /** Crear persona + docente en transacción */
    public function crearPersonaYDocente(array $d): ?int {
        try {
            $this->pdo->beginTransaction();

            $sqlP = "INSERT INTO personas (nombre, fecha_nacimiento, telefono, correo, direccion)
                     VALUES (:nombre, :fn, :tel, :cor, :dir)";
            $stP = $this->pdo->prepare($sqlP);
            $stP->execute([
                'nombre'=>$d['nombre'],
                'fn'=>$d['fecha_nacimiento'] ?: null,
                'tel'=>$d['telefono'] ?: null,
                'cor'=>$d['correo'] ?: null,
                'dir'=>$d['direccion'] ?: null,
            ]);
            $pid = (int)$this->pdo->lastInsertId();

            $sqlD = "INSERT INTO docentes (persona_id, activo) VALUES (:pid, :act)";
            $stD = $this->pdo->prepare($sqlD);
            $stD->execute(['pid'=>$pid, 'act'=> (int)$d['activo']]);

            $idDoc = (int)$this->pdo->lastInsertId();
            $this->pdo->commit();
            return $idDoc;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            error_log('[Docente::crearPersonaYDocente] '.$e->getMessage());
            return null;
        }
    }

    /** Obtener docente + persona por ID */
    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT d.id, d.activo, d.persona_id,
                       p.nombre, p.fecha_nacimiento, p.telefono, p.correo, p.direccion
                  FROM docentes d
                  JOIN personas p ON p.id = d.persona_id
                 WHERE d.id = :id";
        $st = $this->pdo->prepare($sql);
        $st->execute(['id'=>$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /** Actualizar persona + docente */
    public function actualizarPersonaYDocente(array $d): bool {
        try {
            $this->pdo->beginTransaction();

            $st0 = $this->pdo->prepare("SELECT persona_id FROM docentes WHERE id = :id");
            $st0->execute(['id'=>$d['id']]);
            $pid = $st0->fetchColumn();
            if (!$pid) { $this->pdo->rollBack(); return false; }

            $sqlP = "UPDATE personas
                        SET nombre = :nombre,
                            fecha_nacimiento = :fn,
                            telefono = :tel,
                            correo = :cor,
                            direccion = :dir
                      WHERE id = :pid";
            $stP = $this->pdo->prepare($sqlP);
            $stP->execute([
                'nombre'=>$d['nombre'],
                'fn'=>$d['fecha_nacimiento'] ?: null,
                'tel'=>$d['telefono'] ?: null,
                'cor'=>$d['correo'] ?: null,
                'dir'=>$d['direccion'] ?: null,
                'pid'=>$pid,
            ]);

            $sqlD = "UPDATE docentes SET activo = :act WHERE id = :id";
            $stD = $this->pdo->prepare($sqlD);
            $stD->execute(['act'=>(int)$d['activo'], 'id'=>$d['id']]);

            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            error_log('[Docente::actualizarPersonaYDocente] '.$e->getMessage());
            return false;
        }
    }

    /** Alternar activo/inactivo */
    public function toggleActivo(int $id): bool {
        $sql = "UPDATE docentes SET activo = NOT activo WHERE id = :id";
        $st  = $this->pdo->prepare($sql);
        return $st->execute(['id'=>$id]);
    }
}
