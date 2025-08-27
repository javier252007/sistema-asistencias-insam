<?php
// modelos/Usuario.php

class Usuario {
    private $pdo;
    /** Roles permitidos por el sistema (deben existir en el ENUM de la BD) */
    private const ROLES = ['admin','docente','orientador','directora','estudiante'];

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /** Login: trae hash y rol por username */
    public function getUsuarioPorUsername(string $usuario) {
        $sql = "SELECT u.id AS user_id, u.contrasena_hash, u.rol, u.persona_id, u.usuario
                  FROM usuarios u
                 WHERE u.usuario = :usuario";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['usuario' => $usuario]);
        return $stmt->fetch();
    }

    /** Listado para la vista (con persona) */
    public function all(): array {
        $sql = "SELECT u.id, u.usuario, u.rol, u.creado_en,
                       p.nombre AS persona
                  FROM usuarios u
            INNER JOIN personas p ON p.id = u.persona_id
              ORDER BY u.creado_en DESC, u.id DESC";
        return $this->pdo->query($sql)->fetchAll();
    }

    /** Personas que aún no tienen usuario asignado */
    public function personasSinUsuario(): array {
        $sql = "SELECT p.id, p.nombre
                  FROM personas p
             LEFT JOIN usuarios u ON u.persona_id = p.id
                 WHERE u.id IS NULL
              ORDER BY p.nombre ASC";
        return $this->pdo->query($sql)->fetchAll();
    }

    /** Crear usuario con rol */
    public function create(int $persona_id, string $usuario, string $contrasena, string $rol): int {
        if (!in_array($rol, self::ROLES, true)) {
            throw new InvalidArgumentException("Rol inválido");
        }
        $hash = password_hash($contrasena, PASSWORD_BCRYPT);

        $sql = "INSERT INTO usuarios (persona_id, usuario, contrasena_hash, rol)
                VALUES (:persona_id, :usuario, :hash, :rol)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'persona_id' => $persona_id,
            'usuario'    => $usuario,
            'hash'       => $hash,
            'rol'        => $rol
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /** Eliminar usuario */
    public function delete(int $id): void {
        $stmt = $this->pdo->prepare("DELETE FROM usuarios WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    /** ¿Existe username? (para validar unicidad antes de insertar) */
    public function existsUsername(string $usuario): bool {
        $stmt = $this->pdo->prepare("SELECT 1 FROM usuarios WHERE usuario = :u LIMIT 1");
        $stmt->execute(['u' => $usuario]);
        return (bool)$stmt->fetch();
    }

    /** Exponer roles válidos a la vista */
    public function roles(): array {
        return self::ROLES;
    }

    /** Encontrar usuario por ID (incluye datos de persona) */
    public function find(int $id) {
        $sql = "SELECT u.id, u.usuario, u.rol, u.persona_id, p.nombre AS persona
                  FROM usuarios u
            INNER JOIN personas p ON p.id = u.persona_id
                 WHERE u.id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Actualizar usuario.
     * - $contrasena es opcional: si viene vacía/null NO se cambia el hash.
     */
    public function update(int $id, string $usuario, ?string $contrasena, string $rol): void {
        if (!in_array($rol, self::ROLES, true)) {
            throw new InvalidArgumentException("Rol inválido");
        }

        if ($contrasena !== null && $contrasena !== '') {
            $hash = password_hash($contrasena, PASSWORD_BCRYPT);
            $sql = "UPDATE usuarios
                       SET usuario = :usuario,
                           contrasena_hash = :hash,
                           rol = :rol
                     WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'usuario' => $usuario,
                'hash'    => $hash,
                'rol'     => $rol,
                'id'      => $id
            ]);
        } else {
            $sql = "UPDATE usuarios
                       SET usuario = :usuario,
                           rol = :rol
                     WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'usuario' => $usuario,
                'rol'     => $rol,
                'id'      => $id
            ]);
        }
    }

    /**
     * ¿Existe username en otro registro distinto al $excludeId?
     * Útil para validar unicidad en ediciones.
     */
    public function existsUsernameExceptId(string $usuario, int $excludeId): bool {
        $sql = "SELECT 1
                  FROM usuarios
                 WHERE usuario = :u
                   AND id <> :id
                 LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['u' => $usuario, 'id' => $excludeId]);
        return (bool)$stmt->fetch();
    }
}
