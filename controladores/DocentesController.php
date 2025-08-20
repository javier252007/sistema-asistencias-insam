<?php
// controladores/DocentesController.php
// Admin-only: listado + crear + editar + (des)activar docentes

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../modelos/database.php';
require_once __DIR__ . '/../modelos/Docente.php';

class DocentesController {
    private $model;

    public function __construct() {
        $pdo = Database::getInstance();
        $this->model = new Docente($pdo);
    }

    private function requireAdmin(): void {
        if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] ?? '') !== 'admin') {
            header('Location: index.php?action=login');
            exit;
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

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require __DIR__ . '/../views/Docentes/index.php';
    }

    public function create(): void {
        $this->requireAdmin();
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require __DIR__ . '/../views/Docentes/create.php';
    }

    public function store(): void {
        $this->requireAdmin();

        $data = [
            'nombre'           => trim($_POST['nombre'] ?? ''),
            'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? null,
            'telefono'         => trim($_POST['telefono'] ?? ''),
            'correo'           => trim($_POST['correo'] ?? ''),
            'direccion'        => trim($_POST['direccion'] ?? ''),
            'activo'           => (isset($_POST['activo']) && $_POST['activo']==='0') ? 0 : 1,
        ];

        $errores = [];
        if ($data['nombre'] === '') $errores[] = 'El nombre completo es obligatorio.';
        if ($data['correo'] !== '' && !filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'Correo inválido.';
        }

        if (!empty($errores)) {
            $_SESSION['flash'] = ['type'=>'error','messages'=>$errores,'old'=>$data];
            header('Location: index.php?action=docentes_create');
            exit;
        }

        $id = $this->model->crearPersonaYDocente($data);
        if ($id) {
            $_SESSION['flash'] = ['type'=>'success','messages'=>['Docente registrado con éxito.']];
            header('Location: index.php?action=docentes_index');
        } else {
            $_SESSION['flash'] = ['type'=>'error','messages'=>['No se pudo registrar el docente.']];
            header('Location: index.php?action=docentes_create');
        }
        exit;
    }

    public function edit(): void {
        $this->requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) { header('Location: index.php?action=docentes_index'); exit; }

        $doc = $this->model->obtenerPorId($id);
        if (!$doc) {
            $_SESSION['flash'] = ['type'=>'error','messages'=>['Docente no encontrado']];
            header('Location: index.php?action=docentes_index');
            exit;
        }

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require __DIR__ . '/../views/Docentes/edit.php';
    }

    public function update(): void {
        $this->requireAdmin();
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { header('Location: index.php?action=docentes_index'); exit; }

        $data = [
            'id'               => $id,
            'nombre'           => trim($_POST['nombre'] ?? ''),
            'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? null,
            'telefono'         => trim($_POST['telefono'] ?? ''),
            'correo'           => trim($_POST['correo'] ?? ''),
            'direccion'        => trim($_POST['direccion'] ?? ''),
            'activo'           => (isset($_POST['activo']) && $_POST['activo']==='0') ? 0 : 1,
        ];

        $errores = [];
        if ($data['nombre'] === '') $errores[] = 'El nombre completo es obligatorio.';
        if ($data['correo'] !== '' && !filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'Correo inválido.';
        }

        if (!empty($errores)) {
            $_SESSION['flash'] = ['type'=>'error','messages'=>$errores];
            header('Location: index.php?action=docentes_edit&id='.$id);
            exit;
        }

        $ok = $this->model->actualizarPersonaYDocente($data);
        $_SESSION['flash'] = $ok
          ? ['type'=>'success','messages'=>['Docente actualizado.']]
          : ['type'=>'error','messages'=>['No se pudo actualizar.']];
        header('Location: index.php?action=docentes_index');
        exit;
    }

    public function destroy(): void {
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=docentes_index'); exit;
        }
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { header('Location: index.php?action=docentes_index'); exit; }

        $ok = $this->model->toggleActivo($id); // alterna activo/inactivo
        $_SESSION['flash'] = $ok
          ? ['type'=>'success','messages'=>['Estado de docente actualizado.']]
          : ['type'=>'error','messages'=>['No se pudo cambiar el estado.']];
        header('Location: index.php?action=docentes_index');
        exit;
    }
}
