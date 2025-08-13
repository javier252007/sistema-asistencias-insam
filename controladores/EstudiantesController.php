<?php
// controladores/EstudiantesController.php
// Admin-only: formulario y registro de estudiante (personas + estudiantes)

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../modelos/database.php';
require_once __DIR__ . '/../modelos/Estudiante.php';

class EstudiantesController {
    private $model;

    public function __construct() {
        $pdo = Database::getInstance();
        $this->model = new Estudiante($pdo);
    }

    private function requireAdmin(): void {
        if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] ?? '') !== 'admin') {
            header('Location: index.php?action=login');
            exit;
        }
    }

    public function create(): void {
        $this->requireAdmin();
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require __DIR__ . '/../views/Estudiantes/create.php';
    }

    public function store(): void {
        $this->requireAdmin();

        $data = [
            'nombre'           => trim($_POST['nombre'] ?? ''),
            'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? null,
            'telefono'         => trim($_POST['telefono'] ?? ''),
            'correo'           => trim($_POST['correo'] ?? ''),
            'direccion'        => trim($_POST['direccion'] ?? ''),
            'NIE'              => trim($_POST['NIE'] ?? ''),
            'estado'           => trim($_POST['estado'] ?? 'activo'),
            'foto'             => null,
        ];

        $errores = [];
        if ($data['nombre'] === '') $errores[] = 'El nombre completo es obligatorio.';
        if ($data['NIE'] === '')    $errores[] = 'El NIE es obligatorio.';
        if ($data['correo'] !== '' && !filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'Correo inválido.';
        }
        if ($data['NIE'] !== '' && $this->model->existeNIE($data['NIE'])) {
            $errores[] = 'El NIE ya está registrado.';
        }

        if (!empty($_FILES['foto']['name'])) {
            $file = $_FILES['foto'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                $allowed = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp'];
                $mime = mime_content_type($file['tmp_name']);
                if (!isset($allowed[$mime])) {
                    $errores[] = 'Formato de imagen no permitido (usa JPG/PNG/WEBP).';
                } else {
                    $destDir = __DIR__ . '/../public/img/estudiantes';
                    if (!is_dir($destDir)) @mkdir($destDir, 0777, true);
                    $ext = $allowed[$mime];
                    $basename = uniqid('est_', true) . '.' . $ext;
                    $destPath = $destDir . '/' . $basename;
                    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                        $errores[] = 'No se pudo guardar la foto.';
                    } else {
                        $data['foto'] = 'img/estudiantes/' . $basename;
                    }
                }
            } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                $errores[] = 'Error al subir la foto.';
            }
        }

        if (!empty($errores)) {
            $_SESSION['flash'] = ['type'=>'error', 'messages'=>$errores, 'old'=>$data];
            header('Location: index.php?action=estudiantes_create');
            exit;
        }

        $id = $this->model->crearPersonaYEstudiante($data);
        if ($id) {
            $_SESSION['flash'] = ['type'=>'success', 'messages'=>['Estudiante registrado con éxito.']];
        } else {
            $_SESSION['flash'] = ['type'=>'error', 'messages'=>['No se pudo registrar el estudiante.']];
        }
        header('Location: index.php?action=estudiantes_create');
        exit;
    }
}
