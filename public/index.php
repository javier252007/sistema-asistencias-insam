<?php
// public/index.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

/* =========================
 * REQUIRES
 * ========================= */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controladores/AuthController.php';
require_once __DIR__ . '/../controladores/DashboardController.php';
require_once __DIR__ . '/../controladores/EstudiantesController.php';
require_once __DIR__ . '/../controladores/DocentesController.php';
require_once __DIR__ . '/../controladores/GruposController.php';
require_once __DIR__ . '/../controladores/UsuariosController.php';
require_once __DIR__ . '/../controladores/AsistenciasController.php';
require_once __DIR__ . '/../controladores/ClasesController.php';
require_once __DIR__ . '/../controladores/ReportesController.php';
/* Controladores nuevos usados */
require_once __DIR__ . '/../controladores/AsistenciasClaseController.php';
require_once __DIR__ . '/../controladores/FaltasController.php';

/* =========================
 * HELPERS DE SEGURIDAD
 * ========================= */
function require_login(): void {
    if (empty($_SESSION['user_id'])) {
        $_SESSION['error'] = 'Inicia sesión para continuar.';
        header('Location: index.php?action=login');
        exit;
    }
}
function require_admin(): void {
    if (empty($_SESSION['user_id'])) {
        $_SESSION['error'] = 'Inicia sesión para continuar.';
        header('Location: index.php?action=login');
        exit;
    }
    if (($_SESSION['rol'] ?? '') !== 'admin') {
        $_SESSION['error'] = 'Acceso restringido a administradores.';
        header('Location: index.php?action=dashboard');
        exit;
    }
}

/* =========================
 * ROUTER
 * ========================= */
$action = $_GET['action'] ?? 'login';

/* Instancias reusables */
$controllerClases   = new ClasesController();
$controllerReportes = new ReportesController();

switch ($action) {
    /* ---------- AUTENTICACIÓN ---------- */
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

    /* ---------- DASHBOARD ---------- */
    case 'dashboard':
        require_login();
        (new DashboardController())->index();
        break;

    /* ---------- ESTUDIANTES (admin) ---------- */
    case 'estudiantes_index':
        require_login(); require_admin();
        (new EstudiantesController())->index();
        break;

    case 'estudiantes_create':
        require_login(); require_admin();
        (new EstudiantesController())->create();
        break;

    case 'estudiantes_store':
        require_login(); require_admin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') (new EstudiantesController())->store();
        else header('Location: index.php?action=estudiantes_create');
        break;

    case 'estudiantes_edit':
        require_login(); require_admin();
        (new EstudiantesController())->edit();
        break;

    case 'estudiantes_update':
        require_login(); require_admin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') (new EstudiantesController())->update();
        else header('Location: index.php?action=estudiantes_index');
        break;

    case 'estudiantes_destroy':
        require_login(); require_admin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') (new EstudiantesController())->destroy();
        else header('Location: index.php?action=estudiantes_index');
        break;

    /* ---------- DOCENTES (admin) ---------- */
    case 'docentes_index':
        require_login(); require_admin();
        (new DocentesController())->index();
        break;

    case 'docentes_create':
        require_login(); require_admin();
        (new DocentesController())->create();
        break;

    case 'docentes_store':
        require_login(); require_admin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') (new DocentesController())->store();
        else header('Location: index.php?action=docentes_create');
        break;

    case 'docentes_edit':
        require_login(); require_admin();
        (new DocentesController())->edit();
        break;

    case 'docentes_update':
        require_login(); require_admin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') (new DocentesController())->update();
        else header('Location: index.php?action=docentes_index');
        break;

    case 'docentes_destroy':
        require_login(); require_admin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') (new DocentesController())->destroy();
        else header('Location: index.php?action=docentes_index');
        break;

    /* ---------- USUARIOS (admin) ---------- */
    case 'usuarios_index':
        require_login(); require_admin();
        (new UsuariosController())->index();
        break;

    case 'usuarios_create':
        require_login(); require_admin();
        (new UsuariosController())->create();
        break;

    case 'usuarios_store':
        require_login(); require_admin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') (new UsuariosController())->store();
        else header('Location: index.php?action=usuarios_create');
        break;

    case 'usuarios_edit':
        require_login(); require_admin();
        (new UsuariosController())->edit();
        break;

    case 'usuarios_update':
        require_login(); require_admin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') (new UsuariosController())->update();
        else header('Location: index.php?action=usuarios_index');
        break;

    case 'usuarios_destroy':
        require_login(); require_admin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') (new UsuariosController())->destroy();
        else header('Location: index.php?action=usuarios_index');
        break;

    /* ---------- GRUPOS (admin) ---------- */
    case 'grupos_index':
        require_login(); require_admin();
        (new GruposController())->index();
        break;

    case 'grupos_create':
        require_login(); require_admin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') (new GruposController())->store();
        else (new GruposController())->create();
        break;

    case 'grupos_edit':
        require_login(); require_admin();
        (new GruposController())->edit();
        break;

    case 'grupos_update':
        require_login(); require_admin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') (new GruposController())->update();
        else header('Location: index.php?action=grupos_index');
        break;

    case 'grupos_destroy':
        require_login(); require_admin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') (new GruposController())->destroy();
        else header('Location: index.php?action=grupos_index');
        break;

    /* ---------- ASISTENCIAS (kiosco público) ---------- */
    case 'asistencia_registro':
        try { (new AsistenciasController())->registro(); }
        catch (Throwable $e) {
            header('Content-Type: text/plain; charset=utf-8');
            echo "⚠️ Error en asistencia_registro:\n\n{$e->getMessage()}\n\n{$e->getFile()}:{$e->getLine()}";
        }
        break;

    case 'asistencia_historial':
        try { (new AsistenciasController())->historial(); }
        catch (Throwable $e) {
            header('Content-Type: text/plain; charset=utf-8');
            echo "⚠️ Error en asistencia_historial:\n\n{$e->getMessage()}\n\n{$e->getFile()}:{$e->getLine()}";
        }
        break;

    case 'asistencia_historial_estudiante':
        try { (new AsistenciasController())->historialEstudiante(); }
        catch (Throwable $e) {
            header('Content-Type: text/plain; charset=utf-8');
            echo "⚠️ Error en asistencia_historial_estudiante:\n\n{$e->getMessage()}\n\n{$e->getFile()}:{$e->getLine()}";
        }
        break;

    case 'buscar_estudiante':
        try { (new AsistenciasController())->buscarEstudiante(); }
        catch (Throwable $e) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
        }
        break;

    case 'marcar_entrada':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try { (new AsistenciasController())->marcarEntrada(); }
            catch (Throwable $e) {
                $_SESSION['flash_msg'] = 'Error en marcar_entrada: ' . $e->getMessage();
                header('Location: index.php?action=asistencia_registro');
            }
        } else { header('Location: index.php?action=asistencia_registro'); }
        break;

    case 'marcar_salida':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try { (new AsistenciasController())->marcarSalida(); }
            catch (Throwable $e) {
                $_SESSION['flash_msg'] = 'Error en marcar_salida: ' . $e->getMessage();
                header('Location: index.php?action=asistencia_registro');
            }
        } else { header('Location: index.php?action=asistencia_registro'); }
        break;

    /* ---------- CLASES (admin) ---------- */
    case 'clases_index':
        require_login(); require_admin();
        $controllerClases->index();
        break;

    case 'clases_new':
        require_login(); require_admin();
        if (method_exists($controllerClases, 'new')) $controllerClases->new();
        else $controllerClases->create();
        break;

    case 'clases_create':
        require_login(); require_admin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (method_exists($controllerClases, 'store')) $controllerClases->store();
            else $controllerClases->create();
        } else {
            header('Location: index.php?action=clases_new');
        }
        break;

    case 'clases_show':
        require_login(); require_admin();
        $controllerClases->show();
        break;

    case 'clases_asistencia':
        require_login(); require_admin();
        $controllerClases->asistencia();
        break;

    case 'clases_edit':
        require_login(); require_admin();
        $controllerClases->edit();
        break;

    case 'clases_update':
        require_login(); require_admin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controllerClases->update();
        } else {
            header('Location: index.php?action=clases_index');
        }
        break;

    case 'clases_destroy':
        require_login(); require_admin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controllerClases->destroy();
        } else {
            header('Location: index.php?action=clases_index');
        }
        break;

    case 'horarios_disponibles':
        require_login(); require_admin();
        if (method_exists($controllerClases, 'horariosDisponibles')) {
            $controllerClases->horariosDisponibles();
        } else {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([]);
        }
        break;

    /* ---------- REPORTES (limpio) ---------- */
    case 'reportes':
        require_login();
        $controllerReportes->index();
        break;

    case 'reporte_incidentes_historial':
        require_login(); require_admin();
        (new ReportesController())->incidentesHistorial();
        break;

    /* ---------- ACCIONES: asistencia por clase y faltas ---------- */
    case 'asistencias_store':
        require_login(); require_admin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            (new AsistenciasClaseController())->store();
        } else {
            header('Location: index.php?action=clases_index');
        }
        break;

    case 'faltas_store':
        require_login(); require_admin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            (new FaltasController())->store();
        } else {
            header('Location: index.php?action=clases_index');
        }
        break;

    /* ---------- DEFAULT ---------- */
    default:
        if (!empty($_SESSION['user_id'])) {
            header('Location: index.php?action=dashboard');
        } else {
            (new AuthController())->showLogin();
        }
        break;
}
