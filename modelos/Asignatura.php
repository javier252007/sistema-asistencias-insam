<?php
// modelos/Asignatura.php

class Asignatura {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function todas(): array {
        $sql = "SELECT id, nombre FROM asignaturas ORDER BY nombre";
        $st = $this->pdo->query($sql);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtener(int $id): ?array {
        $st = $this->pdo->prepare("SELECT * FROM asignaturas WHERE id = ?");
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function crear(string $nombre): bool {
        $st = $this->pdo->prepare("INSERT INTO asignaturas (nombre) VALUES (:n)");
        return $st->execute([':n' => $nombre]);
    }

    public function actualizar(int $id, string $nombre): bool {
        $st = $this->pdo->prepare("UPDATE asignaturas SET nombre=:n WHERE id=:id");
        return $st->execute([':n' => $nombre, ':id' => $id]);
    }

    public function eliminar(int $id): bool {
        $st = $this->pdo->prepare("DELETE FROM asignaturas WHERE id=?");
        return $st->execute([$id]);
    }
}
