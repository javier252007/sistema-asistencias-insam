<?php
// controladores/GruposController.php
require_once __DIR__ . '/../modelos/Grupo.php';
require_once __DIR__ . '/../modelos/database.php';

class GruposController {
    private $model;

    public function __construct() {
        $pdo = Database::getInstance();
        $this->model = new Grupo($pdo);
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

        $total   = $this->model->contar($q);
        $grupos  = $this->model->listar($q, $per_page, $offset);
        $pages   = max(1, (int)ceil($total / $per_page));

        require __DIR__ . '/../views/Grupos/index.php';

    }

    public function create(): void {
        $this->requireAdmin();
        $modalidades = $this->model->listarModalidades();
        $docentes    = $this->model->listarDocentesActivos();
        require __DIR__ . '/../views/Grupos/create.php';
    }

    public function store(): void {
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=grupos_index'); exit;
        }

        $data = [
            'docente_guia_id' => (int)($_POST['docente_guia_id'] ?? 0),
            'modalidad_id'    => (int)($_POST['modalidad_id'] ?? 0),
            'seccion'         => trim($_POST['seccion'] ?? ''),
            'grado'           => trim($_POST['grado'] ?? ''),
            'anio_lectivo'    => (int)($_POST['anio_lectivo'] ?? 0),
        ];

        $errores = [];
        if ($data['seccion'] === '')    $errores[] = 'La sección es obligatoria.';
        if ($data['grado'] === '')      $errores[] = 'El grado es obligatorio.';
        if ($data['anio_lectivo'] <= 0) $errores[] = 'El año lectivo es obligatorio.';

        if ($errores) {
            $_SESSION['flash'] = ['type'=>'error','messages'=>$errores];
            header('Location: index.php?action=grupos_create'); exit;
        }

        $id = $this->model->crear($data);
        $_SESSION['flash'] = $id
            ? ['type'=>'success','messages'=>['Grupo creado.']]
            : ['type'=>'error','messages'=>['No se pudo crear el grupo.']];
        header('Location: index.php?action=grupos_index'); exit;
    }

    public function edit(): void {
        $this->requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) { header('Location: index.php?action=grupos_index'); exit; }
        $g = $this->model->obtenerPorId($id);
        if (!$g) { header('Location: index.php?action=grupos_index'); exit; }
        $modalidades = $this->model->listarModalidades();
        $docentes    = $this->model->listarDocentesActivos();
        require __DIR__ . '/../views/Grupos/edit.php';
    }

    public function update(): void {
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=grupos_index'); exit;
        }
        $data = [
            'id'              => (int)($_POST['id'] ?? 0),
            'docente_guia_id' => (int)($_POST['docente_guia_id'] ?? 0),
            'modalidad_id'    => (int)($_POST['modalidad_id'] ?? 0),
            'seccion'         => trim($_POST['seccion'] ?? ''),
            'grado'           => trim($_POST['grado'] ?? ''),
            'anio_lectivo'    => (int)($_POST['anio_lectivo'] ?? 0),
        ];

        $errores = [];
        if ($data['id'] <= 0)           $errores[] = 'ID inválido.';
        if ($data['seccion'] === '')    $errores[] = 'La sección es obligatoria.';
        if ($data['grado'] === '')      $errores[] = 'El grado es obligatorio.';
        if ($data['anio_lectivo'] <= 0) $errores[] = 'El año lectivo es obligatorio.';

        if ($errores) {
            $_SESSION['flash'] = ['type'=>'error','messages'=>$errores];
            header('Location: index.php?action=grupos_edit&id='.$data['id']); exit;
        }

        $ok = $this->model->actualizar($data);
        $_SESSION['flash'] = $ok
            ? ['type'=>'success','messages'=>['Grupo actualizado.']]
            : ['type'=>'error','messages'=>['No se pudo actualizar el grupo.']];
        header('Location: index.php?action=grupos_index'); exit;
    }

    public function destroy(): void {
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=grupos_index'); exit;
        }
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { header('Location: index.php?action=grupos_index'); exit; }

        if ($this->model->estaEnUso($id)) {
            $_SESSION['flash'] = ['type'=>'error','messages'=>['No se puede eliminar: el grupo tiene clases asociadas.']];
            header('Location: index.php?action=grupos_index'); exit;
        }

        $ok = $this->model->eliminar($id);
        $_SESSION['flash'] = $ok
            ? ['type'=>'success','messages'=>['Grupo eliminado.']]
            : ['type'=>'error','messages'=>['No se pudo eliminar el grupo.']];
        header('Location: index.php?action=grupos_index'); exit;
    }
}
