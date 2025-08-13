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

$action = $_GET['action'] ?? 'login';

switch ($action) {
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            (new AuthController())->login();
        } else {
            (new AuthController())->showLogin();
        }
        break;

    case 'dashboard':
        (new DashboardController())->index();
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

    case 'logout':
        (new AuthController())->logout();
        break;

    default:
        (new AuthController())->showLogin();
        break;
}
