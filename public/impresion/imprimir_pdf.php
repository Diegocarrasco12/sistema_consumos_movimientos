<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/zebra_print.php';

function back(?string $msg = null, ?string $err = null): void
{
    $params = [];
    if ($msg) $params['msg'] = $msg;
    if ($err) $params['err'] = $err;

    header('Location: index.php?' . http_build_query($params));
    exit;
}

if (!isset($_FILES['pdf'])) {
    back(null, 'No se recibió ningún archivo.');
}

if ($_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
    back(null, 'Error al subir el archivo.');
}

$tmpFile = $_FILES['pdf']['tmp_name'];
$originalName = $_FILES['pdf']['name'] ?? 'archivo.pdf';

$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
if ($ext !== 'pdf') {
    back(null, 'Solo se permiten archivos PDF.');
}

if (!is_dir(PRINT_TMP_DIR)) {
    mkdir(PRINT_TMP_DIR, 0775, true);
}

$targetPath = PRINT_TMP_DIR . '/' . uniqid('print_', true) . '.pdf';

if (!move_uploaded_file($tmpFile, $targetPath)) {
    back(null, 'No se pudo guardar temporalmente el PDF.');
}

/*
========================================
ENVÍO DIRECTO DEL PDF A LA ZEBRA
MISMO MÉTODO QUE TU SISTEMA ZPL
========================================
*/

$pdfData = file_get_contents($targetPath);

$errno = 0;
$errstr = '';

$socket = @fsockopen(ZEBRA_IP, ZEBRA_PORT, $errno, $errstr, 5);

if (!$socket) {
    back(null, "No se pudo conectar a la impresora ($errstr)");
}

stream_set_timeout($socket, 5);

/*
Pequeño salto de línea antes del PDF
algunas Zebra lo requieren
*/
fwrite($socket, "\n");

/*
Enviar PDF completo
*/
fwrite($socket, $pdfData);

fclose($socket);

/*
NO BORRAR EL ARCHIVO TODAVÍA
para poder depurar si es necesario
*/
// @unlink($targetPath);

back('PDF enviado a la Zebra correctamente.');