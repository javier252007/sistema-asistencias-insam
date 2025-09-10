<?php
// controladores/FaltasController.php

require_once __DIR__ . '/../config/database.php';

class FaltasController {
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

    /** Si el catálogo está vacío, crea Leve/Grave/Muy grave */
    private function ensureTiposFaltaBasicos(PDO $pdo): void {
        try {
            $total = (int)$pdo->query("SELECT COUNT(*) FROM tipos_falta")->fetchColumn();
            if ($total > 0) return;

            $sql = "INSERT INTO tipos_falta (codigo, descripcion, tipo, sancion)
                    VALUES
                    ('LE','Falta leve','leve',NULL),
                    ('GR','Falta grave','grave',NULL),
                    ('MG','Falta muy grave','muy grave',NULL)";
            $pdo->exec($sql);
        } catch (Throwable $e) {
            // si falla, seguimos; el insert final validará de nuevo
        }
    }

    /** Comprueba si existe el tipo de falta por id */
    private function existeTipoFalta(PDO $pdo, int $id): bool {
        $st = $pdo->prepare("SELECT 1 FROM tipos_falta WHERE id = :id LIMIT 1");
        $st->execute([':id'=>$id]);
        return (bool)$st->fetchColumn();
    }

    /** Guarda la falta desde el modal (usa incidentes_estudiantes de tu esquema) */
    public function store(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=clases_index'); exit;
        }

        $pdo = $this->pdo();

        $clase_id      = (int)($_POST['clase_id'] ?? 0);
        $estudiante_id = (int)($_POST['estudiante_id'] ?? 0);
        $fecha         = trim((string)($_POST['fecha'] ?? date('Y-m-d')));
        $tipo_id       = (int)($_POST['tipo_id'] ?? 0);               // referencia a tipos_falta.id
        $descripcion   = trim((string)($_POST['descripcion'] ?? '')); // -> observacion
        $enviar_correo = !empty($_POST['enviar_correo']);

        if (!$clase_id || !$estudiante_id || !$tipo_id || $fecha === '') {
            $_SESSION['flash_msg'] = 'Completa fecha y tipo de falta.';
            header('Location: index.php?action=clases_asistencia&id='.$clase_id.'&fecha='.urlencode($fecha)); exit;
        }

        try {
            // Asegura catálogo mínimo
            $this->ensureTiposFaltaBasicos($pdo);

            // Valida que el tipo exista (evita violar FK)
            if (!$this->existeTipoFalta($pdo, $tipo_id)) {
                $_SESSION['flash_msg'] = 'El tipo de falta seleccionado no existe. Vuelve a intentarlo.';
                header('Location: index.php?action=clases_asistencia&id='.$clase_id.'&fecha='.urlencode($fecha));
                exit;
            }

            $sql = "INSERT INTO incidentes_estudiantes
                       (estudiante_id, clase_id, falta_id, registrado_por, observacion, fecha, hora)
                    VALUES
                       (:est, :cla, :fal, :reg, :obs, :fec, :hor)";
            $st = $pdo->prepare($sql);
            $st->execute([
                ':est' => $estudiante_id,
                ':cla' => $clase_id,
                ':fal' => $tipo_id,
                ':reg' => $_SESSION['persona_id'] ?? null,
                ':obs' => $descripcion !== '' ? $descripcion : null,
                ':fec' => $fecha,
                ':hor' => date('H:i:s'),
            ]);

            $_SESSION['flash_msg'] = $enviar_correo
                ? 'Falta registrada y (simulada) notificación por correo.'
                : 'Falta registrada correctamente.';

            header('Location: index.php?action=clases_asistencia&id='.$clase_id.'&fecha='.urlencode($fecha));
            exit;

        } catch (Throwable $e) {
            error_log('[FaltasController::store] '.$e->getMessage());
            $_SESSION['flash_msg'] = 'No se pudo registrar la falta.';
            header('Location: index.php?action=clases_asistencia&id='.$clase_id.'&fecha='.urlencode($fecha));
            exit;
        }
    }
}
