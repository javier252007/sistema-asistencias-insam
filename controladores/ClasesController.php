<?php
// controladores/ClasesController.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../modelos/Clase.php';

class ClasesController {
    private ?PDO $pdo = null;

    /* =========================
       Conexión PDO (lazy)
       ========================= */
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

    /* =========================
       Fallbacks SQL catálogos
       ========================= */
    private function listarDocentes(PDO $pdo): array {
        $sql = "SELECT d.id, p.nombre
                  FROM docentes d
                  JOIN personas p ON p.id = d.persona_id
              ORDER BY p.nombre";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    private function listarGrupos(PDO $pdo): array {
        $sql = "SELECT id, CONCAT(grado,' ',seccion,' (',anio_lectivo,')') AS nombre
                  FROM grupos
              ORDER BY grado, seccion";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    private function listarAsignaturas(PDO $pdo): array {
        $sql = "SELECT id, nombre FROM asignaturas ORDER BY nombre";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    private function listarHorarios(PDO $pdo): array {
        $sql = "SELECT id, numero_periodo, hora_inicio, hora_fin
                  FROM horarios
              ORDER BY numero_periodo, hora_inicio";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================
       Listado
       ========================= */
    public function index(): void {
        require_login(); require_admin();
        $pdo    = $this->pdo();
        $mClase = new Clase($pdo);

        $q      = trim((string)($_GET['q'] ?? ''));
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 10;
        $offset = ($page - 1) * $limit;

        $total  = (int)$mClase->contar($q);
        $rows   = $mClase->listar($q, $limit, $offset);
        $pages  = max(1, (int)ceil($total / $limit));

        require __DIR__ . '/../views/Clases/index.php';
    }

    /* =========================
       Detalle (ver clase)
       ========================= */
    public function show(): void {
        require_login(); require_admin();
        $pdo    = $this->pdo();
        $id     = (int)($_GET['id'] ?? 0);
        if ($id <= 0) { header('Location: index.php?action=clases_index'); exit; }

        $mClase = new Clase($pdo);

        $clase = method_exists($mClase, 'obtenerDetalle')
                 ? $mClase->obtenerDetalle($id)
                 : null;

        if (!$clase || empty($clase['grupo_id'])) {
            $_SESSION['flash'] = ['type'=>'error','messages'=>['Clase o grupo no encontrado.']];
            header('Location: index.php?action=clases_index'); exit;
        }

        $estudiantes = method_exists($mClase, 'estudiantesDeGrupo')
                       ? $mClase->estudiantesDeGrupo((int)$clase['grupo_id'])
                       : [];

        require __DIR__ . '/../views/Clases/show.php';
    }

    /* =========================
       Formularios New/Edit
       ========================= */
    public function create(): void { $this->new(); }

    public function new(): void {
        require_login(); require_admin();
        $pdo = $this->pdo();

        // Catálogos por SQL directo (más simple, sin dependencias de modelos)
        $docentes    = $this->listarDocentes($pdo);
        $grupos      = $this->listarGrupos($pdo);
        $asignaturas = $this->listarAsignaturas($pdo);
        $horarios    = $this->listarHorarios($pdo);

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

        $docentes    = $this->listarDocentes($pdo);
        $grupos      = $this->listarGrupos($pdo);
        $asignaturas = $this->listarAsignaturas($pdo);
        $horarios    = $this->listarHorarios($pdo);

        $isEdit = true;
        require __DIR__ . '/../views/Clases/form.php';
    }

    /* =========================
       Crear / Actualizar
       ========================= */
    public function store(): void { $this->createPost(); }

    public function createPost(): void {
        require_login(); require_admin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php?action=clases_new'); exit; }

        $pdo = $this->pdo();

        $docente_id    = (int)($_POST['docente_id'] ?? 0);
        $grupo_id      = (int)($_POST['grupo_id'] ?? 0);
        $asignatura_id = ($_POST['asignatura_id'] ?? '') !== '' ? (int)$_POST['asignatura_id'] : null;

        // Acepta "Lunes"/"1..7"; tu columna es VARCHAR
        $dia = $_POST['dia'] ?? '';
        $dia = is_numeric($dia) ? (int)$dia : trim((string)$dia);

        // 🔥 NUEVO: SOLO múltiples períodos (horarios[])
        $horariosSel = isset($_POST['horarios']) && is_array($_POST['horarios']) ? array_map('intval', $_POST['horarios']) : [];
        $horariosSel = array_values(array_unique(array_filter($horariosSel, fn($v) => $v > 0)));

        $aula = trim((string)($_POST['aula'] ?? ''));
        if ($aula === '') $aula = null;

        if (!$docente_id || !$grupo_id || $dia === '' || empty($horariosSel)) {
            $_SESSION['flash_msg'] = 'Completa los campos requeridos y selecciona al menos un período.';
            header('Location: index.php?action=clases_new'); exit;
        }

        $mClase = new Clase($pdo);

        try {
            $pdo->beginTransaction();

            $creados  = 0;
            $saltados = []; // horarios con choque

            foreach ($horariosSel as $horario_id) {
                if ($mClase->hayChoque(null, $dia, $horario_id, $docente_id, $grupo_id, $aula)) {
                    $saltados[] = $horario_id;
                    continue;
                }
                $ok = $mClase->crear($docente_id, $grupo_id, $asignatura_id, $dia, $horario_id, $aula);
                if (!$ok) { throw new Exception('Error al crear una de las filas de clase.'); }
                $creados++;
            }

            $pdo->commit();

            if ($creados > 0) {
                $_SESSION['flash_msg'] = "Clase creada en {$creados} período(s) "
                    . (count($saltados) ? "(saltados por choque: ".implode(', ', $saltados).")" : "");
                header('Location: index.php?action=clases_index'); exit;
            } else {
                $_SESSION['flash_msg'] = 'No se creó ninguna clase. Todos los períodos seleccionados tenían choque.';
                header('Location: index.php?action=clases_new'); exit;
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('[ClasesController::createPost] '.$e->getMessage());
            $_SESSION['flash_msg'] = 'Ocurrió un error al crear la clase.';
            header('Location: index.php?action=clases_new'); exit;
        }
    }

    public function update(): void {
        require_login(); require_admin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php?action=clases_index'); exit; }

        $pdo = $this->pdo();

        $id            = (int)($_POST['id'] ?? 0);
        $docente_id    = (int)($_POST['docente_id'] ?? 0);
        $grupo_id      = (int)($_POST['grupo_id'] ?? 0);
        $asignatura_id = ($_POST['asignatura_id'] ?? '') !== '' ? (int)$_POST['asignatura_id'] : null;

        $dia = $_POST['dia'] ?? '';
        $dia = is_numeric($dia) ? (int)$dia : trim((string)$dia);

        // Update mantiene período único (editas una fila concreta)
        $horario_id    = (int)($_POST['horario_id'] ?? 0);
        $aula          = trim((string)($_POST['aula'] ?? ''));
        if ($aula === '') $aula = null;

        if (!$id || !$docente_id || !$grupo_id || $dia === '' || !$horario_id) {
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
        header('Location: index.php?action=clases_index'); exit;
    }

    /* =========================
       Eliminar
       ========================= */
    public function destroy(): void {
        require_login(); require_admin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php?action=clases_index'); exit; }
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { header('Location: index.php?action=clases_index'); exit; }
        $ok = (new Clase($this->pdo()))->eliminar($id);
        $_SESSION['flash_msg'] = $ok ? 'Clase eliminada.' : 'No se pudo eliminar.';
        header('Location: index.php?action=clases_index'); exit;
    }

    /* =========================
       (Opcional) API para periodos libres
       ========================= */
    public function horariosDisponibles(): void {
        require_login(); require_admin();
        header('Content-Type: application/json; charset=utf-8');

        $pdo       = $this->pdo();
        $diaParam  = $_GET['dia'] ?? '';
        $dia       = is_numeric($diaParam) ? (int)$diaParam : trim((string)$diaParam);

        $docenteId = isset($_GET['docente_id']) && $_GET['docente_id'] !== '' ? (int)$_GET['docente_id'] : null;
        $grupoId   = isset($_GET['grupo_id'])   && $_GET['grupo_id']   !== '' ? (int)$_GET['grupo_id']   : null;
        $aula      = ($_GET['aula'] ?? '') !== '' ? trim((string)$_GET['aula']) : null;

        if ($dia === '' || $dia === 0) { echo json_encode([]); return; }

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
              ORDER BY h.numero_periodo, h.hora_inicio";
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
