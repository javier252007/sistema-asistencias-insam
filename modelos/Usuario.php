<?php
// modelos/Usuario.php

class Usuario {
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Busca un estudiante activo por su NIE.
     *
     * @param string $nie
     * @return array|false
     */
    public function getEstudiantePorNIE(string $nie) {
        $sql = "SELECT e.id AS user_id, 'estudiante' AS rol
                  FROM estudiantes e
                 WHERE e.NIE = :nie
                   AND e.estado = 'activo'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['nie' => $nie]);
        return $stmt->fetch();
    }

    /**
     * Busca un usuario (docente, admin, etc.) por su nombre de usuario.
     *
     * @param string $usuario
     * @return array|false
     */
    public function getUsuarioPorUsername(string $usuario) {
        $sql = "SELECT u.id AS user_id,
                       u.contrasena_hash,
                       u.rol
                  FROM usuarios u
                 WHERE u.usuario = :usuario";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['usuario' => $usuario]);
        return $stmt->fetch();
    }
}
