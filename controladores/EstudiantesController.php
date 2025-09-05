<?php
// controladores/EstudiantesController.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../modelos/database.php';
require_once __DIR__ . '/../modelos/Estudiante.php';
require_once __DIR__ . '/../modelos/Grupo.php';

class EstudiantesController {
    private $model;
    private $grupoModel;

    public function __construct() {
        $pdo = Database::getInstance();
        $this->model = new Estudiante($pdo);
        $this->grupoModel = new Grupo($pdo);
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    private function requireAdmin(): void {
        if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] ?? '') !== 'admin') {
            header('Location: index.php?action=login'); exit;
        }
    }

    public function index(): void {
        $this->requireAdmin();
        $q        = trim($_GET['q'] ?? '');
        $page     = max(1, (int)($_GET['page'] ?? 1));
        $per_page = 10;
        $offset   = ($page - 1) * $per_page;

        $total = $this->model->contar($q);
        $rows  = $this->model->listar($q, $per_page, $offset);
        $pages = max(1, (int)ceil($total / $per_page));

        require __DIR__ . '/../views/Estudiantes/index.php';
    }

    public function create(): void {
        $this->requireAdmin();
        $grupos = $this->grupoModel->all();
        require __DIR__ . '/../views/Estudiantes/create.php';
    }

    public function store(): void {
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=estudiantes_index'); exit;
        }
        $data = [
            'nombre'           => trim($_POST['nombre'] ?? ''),
            'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? null,
            'telefono'         => trim($_POST['telefono'] ?? ''),
            'correo'           => trim($_POST['correo'] ?? ''),
            'direccion'        => trim($_POST['direccion'] ?? ''),
            'NIE'              => trim($_POST['NIE'] ?? ''),
            'estado'           => $_POST['estado'] ?? 'activo',
            'grupo_id'         => (int)($_POST['grupo_id'] ?? 0),
        ];

        $errores = [];
        if ($data['nombre'] === '') $errores[] = 'El nombre es obligatorio.';
        if ($data['NIE'] !== '' && $this->model->existeNIE($data['NIE'])) {
            $errores[] = 'El NIE ya existe.';
        }
        if ($data['grupo_id'] <= 0) $errores[] = 'Debe seleccionar un grupo.';
        if ($data['correo'] !== '' && !filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'Correo inválido.';
        }

        if ($errores) {
            $_SESSION['flash'] = ['type'=>'error','messages'=>$errores];
            header('Location: index.php?action=estudiantes_create'); exit;
        }

        $id = $this->model->crearPersonaYEstudiante($data);
        $_SESSION['flash'] = $id
            ? ['type'=>'success','messages'=>['Estudiante registrado.']]
            : ['type'=>'error','messages'=>['No se pudo registrar.']];
        header('Location: index.php?action=estudiantes_index'); exit;
    }

    public function edit(): void {
        $this->requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) { header('Location: index.php?action=estudiantes_index'); exit; }
        $est = $this->model->obtenerPorId($id);
        if (!$est) { header('Location: index.php?action=estudiantes_index'); exit; }
        $grupos = $this->grupoModel->all();
        require __DIR__ . '/../views/Estudiantes/edit.php';
    }

    public function update(): void {
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=estudiantes_index'); exit;
        }
        $data = [
            'id'               => (int)($_POST['id'] ?? 0),
            'persona_id'       => (int)($_POST['persona_id'] ?? 0),
            'nombre'           => trim($_POST['nombre'] ?? ''),
            'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? null,
            'telefono'         => trim($_POST['telefono'] ?? ''),
            'correo'           => trim($_POST['correo'] ?? ''),
            'direccion'        => trim($_POST['direccion'] ?? ''),
            'NIE'              => trim($_POST['NIE'] ?? ''),
            'estado'           => $_POST['estado'] ?? 'activo',
            'grupo_id'         => (int)($_POST['grupo_id'] ?? 0),
        ];

        $errores = [];
        if ($data['id'] <= 0 || $data['persona_id'] <= 0) $errores[] = 'ID inválido.';
        if ($data['nombre'] === '') $errores[] = 'El nombre es obligatorio.';
        if ($data['NIE'] !== '' && $this->model->nieUsadoPorOtro($data['NIE'], $data['id'])) {
            $errores[] = 'El NIE ya está registrado para otro estudiante.';
        }
        if ($data['grupo_id'] <= 0) $errores[] = 'Debe seleccionar un grupo.';
        if ($data['correo'] !== '' && !filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'Correo inválido.';
        }

        if ($errores) {
            $_SESSION['flash'] = ['type'=>'error','messages'=>$errores];
            header('Location: index.php?action=estudiantes_edit&id=' . $data['id']); exit;
        }

        $ok = $this->model->actualizarPersonaYEstudiante($data);
        $_SESSION['flash'] = $ok
            ? ['type'=>'success','messages'=>['Estudiante actualizado.']]
            : ['type'=>'error','messages'=>['No se pudo actualizar.']];

        header('Location: index.php?action=estudiantes_index'); exit;
    }

    public function destroy(): void {
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=estudiantes_index'); exit;
        }
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { header('Location: index.php?action=estudiantes_index'); exit; }

        $ok = $this->model->eliminar($id);
        $_SESSION['flash'] = $ok
            ? ['type'=>'success','messages'=>['Estudiante eliminado permanentemente.']]
            : ['type'=>'error','messages'=>['No se pudo eliminar. Revisa dependencias o FKs.']];

        header('Location: index.php?action=estudiantes_index'); exit;
    }
}
