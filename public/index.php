<?php
// public/index.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controladores/AuthController.php';
require_once __DIR__ . '/../controladores/DashboardController.php';
require_once __DIR__ . '/../controladores/EstudiantesController.php';

// Si ya tienes controladores reales para estos módulos, descomenta:
// require_once __DIR__ . '/../controladores/DocentesController.php';
// require_once __DIR__ . '/../controladores/GruposController.php';
// require_once __DIR__ . '/../controladores/UsuariosController.php';
// require_once __DIR__ . '/../controladores/ClasesController.php';

$action = $_GET['action'] ?? 'login';

// Guard para admin (para placeholders de módulos)
function require_admin(): void {
    if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] ?? '') !== 'admin') {
        header('Location: index.php?action=login');
        exit;
    }
}

switch ($action) {
    // --- Autenticación ---
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            (new AuthController())->login();
        } else {
            (new AuthController())->showLogin();
        }
        break;

    case 'logout':
        (new AuthController())->logout();
        break;

    // --- Dashboard ---
    case 'dashboard':
        (new DashboardController())->index();
        break;

    // --- Estudiantes (admin) ---
    case 'estudiantes_create':
        (new EstudiantesController())->create();
        break;

    case 'estudiantes_store':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            (new EstudiantesController())->store();
        } else {
            header('Location: index.php?action=estudiantes_create');
        }
        break;

    // --- Docentes (placeholder mientras no haya controlador) ---
    case 'docentes_index':
        require_admin();
        require __DIR__ . '/../views/Docentes/index.php';
        break;

    // --- Grupos (placeholder) ---
    case 'grupos_index':
        require_admin();
        require __DIR__ . '/../views/Grupos/index.php';
        break;

    // --- Usuarios (placeholder) ---
    case 'usuarios_index':
        require_admin();
        require __DIR__ . '/../views/Usuarios/index.php';
        break;

    // --- Clases (placeholder) ---
    case 'clases_index':
        require_admin();
        require __DIR__ . '/../views/Clases/index.php';
        break;

    // --- Reportes (placeholder) ---
    case 'reportes':
        require_admin();
        require __DIR__ . '/../views/Reportes/index.php';
        break;

    // --- Default: si hay sesión, al dashboard; si no, al login ---
    default:
        if (isset($_SESSION['user_id'])) {
            header('Location: index.php?action=dashboard');
        } else {
            (new AuthController())->showLogin();
        }
        break;
}
