<?php
// modelos/AsistenciaInstitucional.php

class AsistenciaInstitucional {
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo) {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->pdo = $pdo;
    }

    public function existeEntradaHoy(int $estudiante_id): bool {
        $sql = "SELECT 1 FROM asistencias_institucionales
                 WHERE estudiante_id = :id
                   AND tipo = 'entrada'
                   AND DATE(fecha_hora) = CURDATE()
                 LIMIT 1";
        $st = $this->pdo->prepare($sql);
        $st->execute([':id' => $estudiante_id]);
        return (bool)$st->fetchColumn();
    }

    /* -------- NUEVO -------- */
    public function existeSalidaHoy(int $estudiante_id): bool {
        $sql = "SELECT 1 FROM asistencias_institucionales
                 WHERE estudiante_id = :id
                   AND tipo = 'salida'
                   AND DATE(fecha_hora) = CURDATE()
                 LIMIT 1";
        $st = $this->pdo->prepare($sql);
        $st->execute([':id' => $estudiante_id]);
        return (bool)$st->fetchColumn();
    }

    public function marcarEntrada(int $estudiante_id): void {
        $sql = "INSERT INTO asistencias_institucionales (estudiante_id, tipo, fecha_hora)
                VALUES (:id, 'entrada', NOW())";
        $st = $this->pdo->prepare($sql);
        $st->execute([':id' => $estudiante_id]);
    }

    /* -------- NUEVO -------- */
    public function marcarSalida(int $estudiante_id): void {
        $sql = "INSERT INTO asistencias_institucionales (estudiante_id, tipo, fecha_hora)
                VALUES (:id, 'salida', NOW())";
        $st = $this->pdo->prepare($sql);
        $st->execute([':id' => $estudiante_id]);
    }
}
