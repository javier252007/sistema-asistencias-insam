<?php
// modelos/Usuario.php

class Usuario {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene datos de usuario (docentes/admin) por nombre de usuario
     */
    public function getUsuarioPorUsername(string $usuario) {
        $sql = "SELECT u.id AS user_id, u.contrasena_hash, u.rol
                  FROM usuarios u
                 WHERE u.usuario = :usuario";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['usuario' => $usuario]);
        return $stmt->fetch();
    }
}
