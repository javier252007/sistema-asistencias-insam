<?php
// modelos/Estudiante.php
// Inserta en personas y luego en estudiantes, respetando FK

class Estudiante {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function existeNIE(string $nie): bool {
        $stmt = $this->pdo->prepare('SELECT id FROM estudiantes WHERE NIE = :nie LIMIT 1');
        $stmt->execute(['nie' => $nie]);
        return (bool) $stmt->fetch();
    }

    /**
     * Crea persona y estudiante en una transacción.
     * Retorna el ID de estudiante o null si falla.
     */
    public function crearPersonaYEstudiante(array $d): ?int {
        try {
            $this->pdo->beginTransaction();

            // 1) personas
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

            // 2) estudiantes
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
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            // Aquí podrías loguear el error: error_log($e->getMessage());
            return null;
        }
    }
}
