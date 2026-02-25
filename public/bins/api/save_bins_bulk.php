<?php

declare(strict_types=1);

/* ===============================
| DEBUG (solo pruebas)
|=============================== */
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

/* ===============================
| Timezone
|=============================== */
date_default_timezone_set('America/Santiago');

/* ===============================
| DB BINS (OBLIGATORIO)
|=============================== */
require_once __DIR__ . '/../../../config/db_bins.php';
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/bins/uploads/documentos_bins/';
$uploadUrl = '/bins/uploads/documentos_bins/';


if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}


/* ===============================
| Validar método
|=============================== */
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit('Método no permitido');
}

/* ===============================
| Validar datos
|=============================== */
$rows = $_POST['rows'] ?? [];

if (!is_array($rows) || empty($rows)) {
    header('Location: ../list_bins.php');
    exit;
}

/* ===============================
| Preparar UPDATE (mysqli BINS)
|=============================== */
$stmt = $mysqliBins->prepare("
    UPDATE movimientos_bins
    SET documento = ?, estado_bin = ?
    WHERE id = ?
");
$stmtFile = $mysqliBins->prepare("
    UPDATE movimientos_bins
    SET archivo = ?
    WHERE id = ?
");

if (!$stmtFile) {
    die('❌ Error al preparar UPDATE archivo: ' . $mysqliBins->error);
}


if (!$stmt) {
    die('❌ Error al preparar UPDATE: ' . $mysqliBins->error);
}

/* ===============================
| Procesar filas
|=============================== */
$actualizados = 0;

foreach ($rows as $row) {

    $id         = (int)($row['id'] ?? 0);
    $documento  = trim((string)($row['documento'] ?? ''));
    $estado_bin = trim((string)($row['estado_bin'] ?? ''));

    if ($id <= 0) {
        continue;
    }

    $stmt->bind_param('ssi', $documento, $estado_bin, $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $actualizados++;
        }
    }
    // ===============================
// Procesar archivo (si existe)
// ===============================
if (
    !empty($_FILES['files']['name'][$id]) &&
    $_FILES['files']['error'][$id] === UPLOAD_ERR_OK
) {

    $tmpName = $_FILES['files']['tmp_name'][$id];
    $origName = $_FILES['files']['name'][$id];
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

    $allowed = ['jpg', 'jpeg', 'png', 'pdf'];

    if (in_array($ext, $allowed, true)) {

        $fileName = 'bin_' . $id . '_' . time() . '.' . $ext;
        $destPath = $uploadDir . $fileName;
        $dbPath   = $uploadUrl . $fileName;

        if (move_uploaded_file($tmpName, $destPath)) {
            $stmtFile->bind_param('si', $dbPath, $id);
            $stmtFile->execute();
        }
    }
}

}

$stmt->close();
$stmtFile->close();

/* ===============================
| Redirigir (post-guardado)
|=============================== */
$returnUrl = $_POST['return_url'] ?? '../list_bins.php';

// Seguridad básica (evita redirecciones externas)
if (!str_contains($returnUrl, 'list_bins.php')) {
    $returnUrl = '../list_bins.php';
}

header('Location: ' . $returnUrl);
exit;
