<?php
/**
 * upload_bin_file.php
 * - Subida de archivo (foto/PDF) por movimiento BIN
 * - Usa db_bins.php (mysqli)
 * - Guarda ruta en movimientos_bins.archivo
 * - Redirige de vuelta al listado
 */

declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../../config/db_bins.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido');
}

/* ===============================
   Validaciones básicas
================================ */
$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    exit('ID inválido');
}

if (
    !isset($_FILES['archivo']) ||
    $_FILES['archivo']['error'] !== UPLOAD_ERR_OK
) {
    http_response_code(400);
    exit('Archivo no recibido');
}

/* ===============================
   Config subida
================================ */
$uploadDirFs = __DIR__ . '/../../uploads/documentos_bins/';
$uploadDirDb = 'uploads/documentos_bins/';

if (!is_dir($uploadDirFs)) {
    mkdir($uploadDirFs, 0755, true);
}

$file = $_FILES['archivo'];

$maxSize = 5 * 1024 * 1024; // 5 MB
if ($file['size'] > $maxSize) {
    exit('Archivo demasiado grande (máx 5MB)');
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'pdf'];

if (!in_array($ext, $allowed, true)) {
    exit('Formato no permitido');
}

/* ===============================
   Nombre único
================================ */
$filename = 'bin_mov_' . $id . '_' . date('Ymd_His') . '.' . $ext;
$pathFs   = $uploadDirFs . $filename;
$pathDb   = $uploadDirDb . $filename;

/* ===============================
   Mover archivo
================================ */
if (!move_uploaded_file($file['tmp_name'], $pathFs)) {
    exit('Error al guardar archivo');
}

/* ===============================
   Guardar en BD
================================ */
$stmt = $mysqliBins->prepare(
    "UPDATE movimientos_bins 
     SET archivo = ?
     WHERE id = ?
     LIMIT 1"
);

if (!$stmt) {
    unlink($pathFs);
    exit('Error SQL (prepare)');
}

$stmt->bind_param('si', $pathDb, $id);

if (!$stmt->execute()) {
    unlink($pathFs);
    exit('Error SQL (execute)');
}

$stmt->close();

/* ===============================
   Redirigir al listado
================================ */
header('Location: ../list_bins.php');
exit;
