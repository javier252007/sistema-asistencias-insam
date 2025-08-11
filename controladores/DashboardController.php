<?php
// controladores/DashboardController.php

class DashboardController {
    public function index(): void {
        // Si no hay sesión iniciada, redirige al login
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php');
            exit;
        }
        // Recupera el rol y carga la vista
        $rol = $_SESSION['rol'];
        require __DIR__ . '/../views/dashboard.php';
    }
}
