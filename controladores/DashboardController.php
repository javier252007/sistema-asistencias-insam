<?php
// controladores/DashboardController.php

class DashboardController {
    private function requireLogin(): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?action=login');
            exit;
        }
    }

    public function index(): void {
        $this->requireLogin();
        // Evita notice si por alguna razón no está seteado el rol
        $rol = $_SESSION['rol'] ?? 'invitado';
        require __DIR__ . '/../views/dashboard.php';
    }
}
