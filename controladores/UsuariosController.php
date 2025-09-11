<?php
// controladores/UsuariosController.php
require_once __DIR__ . '/../modelos/database.php';
require_once __DIR__ . '/../modelos/Usuario.php';

class UsuariosController {
    private $usuarioModel;

    public function __construct() {
        $pdo = Database::getInstance();
        $this->usuarioModel = new Usuario($pdo);
    }

    private function requireAdmin(): void {
        if (empty($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
            $_SESSION['error'] = 'Acceso restringido a administradores.';
            header('Location: index.php?action=dashboard');
            exit;
        }
    }

    public function index(): void {
        $this->requireAdmin();
        $usuarios = $this->usuarioModel->all();
        require __DIR__ . '/../views/Usuarios/index.php';
    }

    public function create(): void {
        $this->requireAdmin();
        $personas = $this->usuarioModel->personasSinUsuario();
        $roles    = $this->usuarioModel->roles();
        require __DIR__ . '/../views/Usuarios/create.php';
    }

    public function store(): void {
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=usuarios_index');
            exit;
        }

        $persona_id = (int)($_POST['persona_id'] ?? 0);
        $usuario    = trim($_POST['usuario'] ?? '');
        $contrasena = trim($_POST['contrasena'] ?? '');
        $rol        = trim($_POST['rol'] ?? '');

        if ($persona_id <= 0 || $usuario === '' || $contrasena === '' || $rol === '') {
            $_SESSION['error'] = 'Completa todos los campos.';
            header('Location: index.php?action=usuarios_create');
            exit;
        }

        if (strlen($usuario) < 3) {
            $_SESSION['error'] = 'El usuario debe tener al menos 3 caracteres.';
            header('Location: index.php?action=usuarios_create');
            exit;
        }
        if (strlen($contrasena) < 4) {
            $_SESSION['error'] = 'La contraseña debe tener al menos 4 caracteres.';
            header('Location: index.php?action=usuarios_create');
            exit;
        }
        if ($this->usuarioModel->existsUsername($usuario)) {
            $_SESSION['error'] = 'El nombre de usuario ya existe.';
            header('Location: index.php?action=usuarios_create');
            exit;
        }

        try {
            $this->usuarioModel->create($persona_id, $usuario, $contrasena, $rol);
            $_SESSION['success'] = 'Usuario creado correctamente.';
            header('Location: index.php?action=usuarios_index');
            exit;
        } catch (Throwable $e) {
            $_SESSION['error'] = 'Error al crear usuario: ' . $e->getMessage();
            header('Location: index.php?action=usuarios_create');
            exit;
        }
    }

    public function edit(): void {
        $this->requireAdmin();

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: index.php?action=usuarios_index');
            exit;
        }

        $usuario = $this->usuarioModel->find($id);
        if (!$usuario) {
            $_SESSION['error'] = 'Usuario no encontrado.';
            header('Location: index.php?action=usuarios_index');
            exit;
        }

        $roles = $this->usuarioModel->roles();
        require __DIR__ . '/../views/Usuarios/edit.php';
    }

    public function update(): void {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=usuarios_index');
            exit;
        }

        $id         = (int)($_POST['id'] ?? 0);
        $usuario    = trim($_POST['usuario'] ?? '');
        $contrasena = trim($_POST['contrasena'] ?? '');
        $rol        = trim($_POST['rol'] ?? '');

        if ($id <= 0 || $usuario === '' || $rol === '') {
            $_SESSION['error'] = 'Usuario y rol son obligatorios.';
            header('Location: index.php?action=usuarios_edit&id=' . $id);
            exit;
        }

        if (strlen($usuario) < 3) {
            $_SESSION['error'] = 'El usuario debe tener al menos 3 caracteres.';
            header('Location: index.php?action=usuarios_edit&id=' . $id);
            exit;
        }

        if ($contrasena !== '' && strlen($contrasena) < 4) {
            $_SESSION['error'] = 'La contraseña debe tener al menos 4 caracteres.';
            header('Location: index.php?action=usuarios_edit&id=' . $id);
            exit;
        }

        if ($this->usuarioModel->existsUsernameExceptId($usuario, $id)) {
            $_SESSION['error'] = 'Ya existe otro usuario con ese nombre.';
            header('Location: index.php?action=usuarios_edit&id=' . $id);
            exit;
        }

        try {
            $this->usuarioModel->update($id, $usuario, ($contrasena === '' ? null : $contrasena), $rol);
            $_SESSION['success'] = 'Usuario actualizado.';
            header('Location: index.php?action=usuarios_index');
            exit;
        } catch (Throwable $e) {
            $_SESSION['error'] = 'Error al actualizar: ' . $e->getMessage();
            header('Location: index.php?action=usuarios_edit&id=' . $id);
            exit;
        }
    }

    public function destroy(): void {
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=usuarios_index');
            exit;
        }
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['error'] = 'ID inválido.';
            header('Location: index.php?action=usuarios_index');
            exit;
        }
        try {
            $this->usuarioModel->delete($id);
            $_SESSION['success'] = 'Usuario eliminado.';
        } catch (Throwable $e) {
            $_SESSION['error'] = 'No se pudo eliminar: ' . $e->getMessage();
        }
        header('Location: index.php?action=usuarios_index');
        exit;
    }

    /* =========================
       PERFIL (nuevo/corregido)
       ========================= */
    public function perfil(): void {
        if (empty($_SESSION['user_id'])) {
            header('Location: index.php?action=login'); exit;
        }

        $pdo = Database::getInstance();
        $userId = (int)$_SESSION['user_id'];

        $sql = "SELECT 
                    u.id,
                    u.usuario,
                    u.rol,
                    u.persona_id,
                    p.nombre   AS nombre,
                    p.correo   AS correo,
                    p.telefono AS telefono,
                    p.direccion AS direccion
                FROM usuarios u
           LEFT JOIN personas p ON p.id = u.persona_id
               WHERE u.id = :id
               LIMIT 1";
        $st = $pdo->prepare($sql);
        $st->execute([':id' => $userId]);
        $data = $st->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            $_SESSION['error'] = 'Usuario no encontrado.';
            header('Location: index.php?action=dashboard'); exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre    = trim($_POST['nombre']    ?? '');
            $correo    = trim($_POST['correo']    ?? '');
            $telefono  = trim($_POST['telefono']  ?? '');
            $direccion = trim($_POST['direccion'] ?? '');

            if ((int)$data['persona_id'] > 0) {
                $up = $pdo->prepare("UPDATE personas
                                        SET nombre = :n, correo = :c, telefono = :t, direccion = :d
                                      WHERE id = :pid");
                $up->execute([
                    ':n' => $nombre,
                    ':c' => ($correo !== '' ? $correo : null),
                    ':t' => ($telefono !== '' ? $telefono : null),
                    ':d' => ($direccion !== '' ? $direccion : null),
                    ':pid' => (int)$data['persona_id']
                ]);
                $_SESSION['success'] = 'Perfil actualizado.';
                header('Location: index.php?action=perfil'); exit;
            } else {
                $_SESSION['error'] = 'No hay persona asociada a este usuario.';
                header('Location: index.php?action=perfil'); exit;
            }
        }

        require __DIR__ . '/../views/Usuarios/perfil.php';
    }
}