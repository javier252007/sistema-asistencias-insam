<?php
// controladores/AuthController.php

require_once __DIR__ . '/../config/database.php';
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
        $usuario    = trim($_POST['usuario'] ?? '');
        $contrasena = $_POST['contrasena'] ?? '';

        if ($usuario === '' || $contrasena === '') {
            $_SESSION['error'] = 'Completa usuario y contraseña.';
            header('Location: index.php?action=login');
            exit;
        }

        $data = $this->usuarioModel->getUsuarioPorUsername($usuario);
        if ($data && password_verify($contrasena, $data['contrasena_hash'])) {
            $_SESSION['user_id'] = $data['user_id'];
            $_SESSION['rol']     = $data['rol'];
            header('Location: index.php?action=dashboard');
            exit;
        } else {
            $_SESSION['error'] = 'Usuario o contraseña incorrectos.';
            header('Location: index.php?action=login');
            exit;
        }
    }

    public function logout(): void {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['error'] = 'Sesión cerrada correctamente.';
        header('Location: index.php?action=login');
        exit;
    }
}
