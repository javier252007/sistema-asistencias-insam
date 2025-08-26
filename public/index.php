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
require_once __DIR__ . '/../controladores/DocentesController.php';
require_once __DIR__ . '/../controladores/GruposController.php';



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
case 'estudiantes_index':
    (new EstudiantesController())->index();
    break;

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

case 'estudiantes_edit':
    (new EstudiantesController())->edit();
    break;

case 'estudiantes_update':
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        (new EstudiantesController())->update();
    } else {
        header('Location: index.php?action=estudiantes_index');
    }
    break;

case 'estudiantes_destroy':
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        (new EstudiantesController())->destroy();
    } else {
        header('Location: index.php?action=estudiantes_index');
    }
    break;


// --- Docentes (admin) ---
case 'docentes_index':
    (new DocentesController())->index();
    break;

case 'docentes_create':
    (new DocentesController())->create();
    break;

case 'docentes_store':
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        (new DocentesController())->store();
    } else {
        header('Location: index.php?action=docentes_create');
    }
    break;

case 'docentes_edit':
    (new DocentesController())->edit();
    break;

case 'docentes_update':
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        (new DocentesController())->update();
    } else {
        header('Location: index.php?action=docentes_index');
    }
    break;

case 'docentes_destroy':
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        (new DocentesController())->destroy(); // desactivar/activar
    } else {
        header('Location: index.php?action=docentes_index');
    }
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


        // --- Grupos ---
case 'grupos_index':
    (new GruposController())->index();
    break;

case 'grupos_create':
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        (new GruposController())->store();
    } else {
        (new GruposController())->create();
    }
    break;

case 'grupos_edit':
    (new GruposController())->edit();
    break;

case 'grupos_update':
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        (new GruposController())->update();
    } else {
        header('Location: index.php?action=grupos_index');
    }
    break;

case 'grupos_destroy':
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        (new GruposController())->destroy();
    } else {
        header('Location: index.php?action=grupos_index');
    }
    break;

}
