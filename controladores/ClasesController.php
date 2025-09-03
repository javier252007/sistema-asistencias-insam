<?php
// controladores/ClasesController.php
// Versión que se adapta a los métodos ya existentes en tus modelos.
// No requiere que exista ->todos(); detecta listar/getAll/all/obtenerTodos, etc.

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../modelos/Clase.php';
require_once __DIR__ . '/../modelos/Docente.php';
require_once __DIR__ . '/../modelos/Grupo.php';
require_once __DIR__ . '/../modelos/Asignatura.php';
// Horarios puede no existir como modelo en tu proyecto; por eso uso fallback SQL más abajo.

class ClasesController {
    private ?PDO $pdo = null;

    private function pdo(): PDO {
        if ($this->pdo instanceof PDO) return $this->pdo;
        if (class_exists('Database') && method_exists('Database','getInstance')) {
            $this->pdo = Database::getInstance();
        } else {
            $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4';
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }
        return $this->pdo;
    }

    /** 
     * Llama al primer método existente de la lista en el modelo dado.
     * Si ninguno existe, devuelve null para permitir fallback SQL.
     */
    private function callFirstAvailable(object $model, array $candidates): ?array {
        foreach ($candidates as $m) {
            if (method_exists($model, $m)) {
                // si el método admite parámetros, aquí asumimos lista simple sin parámetros
                return $model->{$m}();
            }
        }
        return null;
    }

    /** Fallbacks SQL por si el modelo no ofrece método de lista */
    private function listarDocentesFallback(PDO $pdo): array {
        $sql = "SELECT d.id, p.nombre
                  FROM docentes d
                  JOIN personas p ON p.id = d.persona_id
              ORDER BY p.nombre";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    private function listarGruposFallback(PDO $pdo): array {
        $sql = "SELECT id, CONCAT(grado,' ',seccion,' (',anio_lectivo,')') AS nombre
                  FROM grupos
              ORDER BY grado, seccion";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    private function listarAsignaturasFallback(PDO $pdo): array {
        // Ajusta los campos si tu tabla difiere
        $sql = "SELECT id, nombre FROM asignaturas ORDER BY nombre";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    private function listarHorariosFallback(PDO $pdo): array {
        // Si no tienes modelo Horario, leo directo la tabla
        $sql = "SELECT id, numero_periodo, hora_inicio, hora_fin
                  FROM horarios
              ORDER BY numero_periodo";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function index(): void {
        require_login(); require_admin();
        $pdo    = $this->pdo();
        $mClase = new Clase($pdo);

        $q      = trim($_GET['q'] ?? '');
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 10;
        $offset = ($page - 1) * $limit;

        $total  = $mClase->contar($q);
        $rows   = $mClase->listar($q, $limit, $offset);
        $pages  = (int)ceil(max(1, $total) / $limit);

        require __DIR__ . '/../views/Clases/index.php';
    }

    // Para compatibilidad con tu routing existente que usa create() para mostrar form:
    public function create(): void { $this->new(); }

    public function new(): void {
        require_login(); require_admin();
        $pdo = $this->pdo();

        // DOCENTES
        $docModel = new Docente($pdo);
        $docentes = $this->callFirstAvailable($docModel, ['todos','listar','getAll','all','obtenerTodos','listAll']);
        if ($docentes === null) $docentes = $this->listarDocentesFallback($pdo);

        // GRUPOS
        $gruModel = new Grupo($pdo);
        $grupos   = $this->callFirstAvailable($gruModel, ['todos','listar','getAll','all','obtenerTodos','listAll']);
        if ($grupos === null) $grupos = $this->listarGruposFallback($pdo);

        // ASIGNATURAS
        $asiModel = new Asignatura($pdo);
        $asignaturas = $this->callFirstAvailable($asiModel, ['todas','todos','listar','getAll','all','obtenerTodos','listAll']);
        if ($asignaturas === null) $asignaturas = $this->listarAsignaturasFallback($pdo);

        // HORARIOS (por número de período)
        if (class_exists('Horario')) {
            $horModel = new Horario($pdo);
            $horarios = $this->callFirstAvailable($horModel, ['todos','listar','getAll','all','obtenerTodos','listAll']);
            if ($horarios === null) $horarios = $this->listarHorariosFallback($pdo);
        } else {
            $horarios = $this->listarHorariosFallback($pdo);
        }

        $isEdit = false;
        require __DIR__ . '/../views/Clases/form.php';
    }

    public function edit(): void {
        require_login(); require_admin();
        $pdo = $this->pdo();
        $id  = (int)($_GET['id'] ?? 0);
        if ($id <= 0) { header('Location: index.php?action=clases_index'); exit; }

        $mClase = new Clase($pdo);
        $clase  = $mClase->obtener($id);
        if (!$clase) { $_SESSION['flash_msg'] = 'Clase no encontrada.'; header('Location: index.php?action=clases_index'); exit; }

        // Listas igual que en new()
        $docModel = new Docente($pdo);
        $docentes = $this->callFirstAvailable($docModel, ['todos','listar','getAll','all','obtenerTodos','listAll']);
        if ($docentes === null) $docentes = $this->listarDocentesFallback($pdo);

        $gruModel = new Grupo($pdo);
        $grupos   = $this->callFirstAvailable($gruModel, ['todos','listar','getAll','all','obtenerTodos','listAll']);
        if ($grupos === null) $grupos = $this->listarGruposFallback($pdo);

        $asiModel = new Asignatura($pdo);
        $asignaturas = $this->callFirstAvailable($asiModel, ['todas','todos','listar','getAll','all','obtenerTodos','listAll']);
        if ($asignaturas === null) $asignaturas = $this->listarAsignaturasFallback($pdo);

        if (class_exists('Horario')) {
            $horModel = new Horario($pdo);
            $horarios = $this->callFirstAvailable($horModel, ['todos','listar','getAll','all','obtenerTodos','listAll']);
            if ($horarios === null) $horarios = $this->listarHorariosFallback($pdo);
        } else {
            $horarios = $this->listarHorariosFallback($pdo);
        }

        $isEdit = true;
        require __DIR__ . '/../views/Clases/form.php';
    }

    public function store(): void { $this->createPost(); } // compat
    public function createPost(): void {
        require_login(); require_admin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php?action=clases_new'); exit; }

        $pdo = $this->pdo();

        $docente_id    = (int)($_POST['docente_id'] ?? 0);
        $grupo_id      = (int)($_POST['grupo_id'] ?? 0);
        $asignatura_id = ($_POST['asignatura_id'] ?? '') !== '' ? (int)$_POST['asignatura_id'] : null;
        $dia           = trim($_POST['dia'] ?? '');
        $horario_id    = (int)($_POST['horario_id'] ?? 0);
        $aula          = trim($_POST['aula'] ?? '');
        if ($aula === '') $aula = null;

        if (!$docente_id || !$grupo_id || !$dia || !$horario_id) {
            $_SESSION['flash_msg'] = 'Completa los campos requeridos.';
            header('Location: index.php?action=clases_new'); exit;
        }

        $mClase = new Clase($pdo);
        if ($mClase->hayChoque(null, $dia, $horario_id, $docente_id, $grupo_id, $aula)) {
            $_SESSION['flash_msg'] = 'Choque de horario: docente/grupo/aula ya ocupados en ese período.';
            header('Location: index.php?action=clases_new'); exit;
        }

        $ok = $mClase->crear($docente_id, $grupo_id, $asignatura_id, $dia, $horario_id, $aula);
        $_SESSION['flash_msg'] = $ok ? 'Clase creada.' : 'No se pudo crear.';
        header('Location: index.php?action=clases_index');
    }

    public function update(): void {
        require_login(); require_admin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php?action=clases_index'); exit; }

        $pdo = $this->pdo();

        $id            = (int)($_POST['id'] ?? 0);
        $docente_id    = (int)($_POST['docente_id'] ?? 0);
        $grupo_id      = (int)($_POST['grupo_id'] ?? 0);
        $asignatura_id = ($_POST['asignatura_id'] ?? '') !== '' ? (int)$_POST['asignatura_id'] : null;
        $dia           = trim($_POST['dia'] ?? '');
        $horario_id    = (int)($_POST['horario_id'] ?? 0);
        $aula          = trim($_POST['aula'] ?? '');
        if ($aula === '') $aula = null;

        if (!$id || !$docente_id || !$grupo_id || !$dia || !$horario_id) {
            $_SESSION['flash_msg'] = 'Completa los campos requeridos.';
            header('Location: index.php?action=clases_index'); exit;
        }

        $mClase = new Clase($pdo);
        if ($mClase->hayChoque($id, $dia, $horario_id, $docente_id, $grupo_id, $aula)) {
            $_SESSION['flash_msg'] = 'Choque de horario al actualizar.';
            header('Location: index.php?action=clases_edit&id='.$id); exit;
        }

        $ok = $mClase->actualizar($id, $docente_id, $grupo_id, $asignatura_id, $dia, $horario_id, $aula);
        $_SESSION['flash_msg'] = $ok ? 'Clase actualizada.' : 'No se pudo actualizar.';
        header('Location: index.php?action=clases_index');
    }

    public function destroy(): void {
        require_login(); require_admin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php?action=clases_index'); exit; }
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { header('Location: index.php?action=clases_index'); exit; }
        $ok = (new Clase($this->pdo()))->eliminar($id);
        $_SESSION['flash_msg'] = $ok ? 'Clase eliminada.' : 'No se pudo eliminar.';
        header('Location: index.php?action=clases_index');
    }

    /** (Opcional) endpoint AJAX si lo vas a usar; hace fallback SQL a horarios */
    public function horariosDisponibles(): void {
        require_login(); require_admin();
        header('Content-Type: application/json; charset=utf-8');

        $pdo       = $this->pdo();
        $dia       = trim($_GET['dia'] ?? '');
        $docenteId = isset($_GET['docente_id']) && $_GET['docente_id'] !== '' ? (int)$_GET['docente_id'] : null;
        $grupoId   = isset($_GET['grupo_id'])   && $_GET['grupo_id']   !== '' ? (int)$_GET['grupo_id']   : null;
        $aula      = ($_GET['aula'] ?? '') !== '' ? trim($_GET['aula']) : null;

        if ($dia === '') { echo json_encode([]); return; }

        // Filtra períodos no ocupados por docente/grupo/aula en ese día
        $sql = "SELECT h.*
                  FROM horarios h
                 WHERE NOT EXISTS (
                        SELECT 1
                          FROM clases c
                         WHERE c.dia = :dia
                           AND c.horario_id = h.id
                           AND (
                                 (:docenteId IS NOT NULL AND c.docente_id = :docenteId)
                              OR (:grupoId   IS NOT NULL AND c.grupo_id   = :grupoId)
                              OR (:aula      IS NOT NULL AND c.aula       = :aula)
                           )
                 )
              ORDER BY h.numero_periodo";
        $st = $pdo->prepare($sql);
        $st->execute([
            ':dia'       => $dia,
            ':docenteId' => $docenteId,
            ':grupoId'   => $grupoId,
            ':aula'      => $aula
        ]);
        echo json_encode($st->fetchAll(PDO::FETCH_ASSOC));
    }
}
