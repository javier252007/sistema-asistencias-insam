<?php
// public/index.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controladores/AuthController.php';

$action = $_GET['action'] ?? 'show';
$auth   = new AuthController();

if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth->login();
} else {
    $auth->showLogin();
}

