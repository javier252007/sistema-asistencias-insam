<?php
// controladores/AuthController.php
require_once __DIR__ . '/../modelos/database.php';
require_once __DIR__ . '/../modelos/Usuario.php';

class AuthController {
    private $usuarioModel;

    public function __construct() {
        $pdo = Database::getInstance();
        $this->usuarioModel = new Usuario($pdo);
    }

    public function showLogin(): void {
        $error = $_SESSION['error'] ?? '';
        unset($_SESSION['error']);
        require __DIR__ . '/../views/login.php';
    }

    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=login');
            exit;
        }
        $usuario    = trim($_POST['usuario'] ?? '');
        $contrasena = trim($_POST['contrasena'] ?? '');

        if ($usuario === '' || $contrasena === '') {
            $_SESSION['error'] = 'Usuario y contraseña son obligatorios.';
            header('Location: index.php?action=login');
            exit;
        }

        $row = $this->usuarioModel->getUsuarioPorUsername($usuario);
        if (!$row || empty($row['contrasena_hash']) || !password_verify($contrasena, $row['contrasena_hash'])) {
            $_SESSION['error'] = 'Credenciales inválidas.';
            header('Location: index.php?action=login');
            exit;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$row['user_id'];
        $_SESSION['usuario'] = $row['usuario'];
        $_SESSION['rol']     = $row['rol'];

        header('Location: index.php?action=dashboard');
        exit;
    }

    public function logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'domain'   => $p['domain'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => 'Lax',
        ]);
    }
        session_destroy();
        session_start();
        $_SESSION['error'] = 'Sesión cerrada correctamente.';
        header('Location: index.php?action=login');
        exit;
    }
}
