<?php

declare(strict_types=1);

/**
 * guardar_movimiento.php — CONTROL BINS (BATCH)
 * BLINDADO PARA LOCAL + PRODUCCIÓN
 */

ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

date_default_timezone_set('America/Santiago');

require_once __DIR__ . '/../../../config/db_bins.php';

function respond(array $payload, int $code = 200): void
{
    http_response_code($code);
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

try {

    /* =======================
       0) MÉTODO
    ======================= */
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        throw new Exception('Método no permitido');
    }

    /* =======================
       1) INPUTS (BATCH)
    ======================= */
    $binsRaw    = $_POST['bins'] ?? '';
    $tipo       = trim((string)($_POST['tipo'] ?? ''));
    $documento  = trim((string)($_POST['documento'] ?? ''));
    $proveedor  = trim((string)($_POST['proveedor'] ?? ''));
    $estadoBin  = trim((string)($_POST['estado_bin'] ?? ''));


    $bins = json_decode((string)$binsRaw, true);

    if (
        !is_array($bins) ||
        count($bins) === 0 ||
        $tipo === '' ||
        $documento === '' ||
        $proveedor === ''
    ) {
        throw new Exception('Faltan datos obligatorios');
    }

    if (!in_array($proveedor, ['INNPACK', 'SUPERFRUIT'], true)) {
        throw new Exception('Proveedor inválido');
    }

    if (!in_array($tipo, ['ENTRADA', 'SALIDA', 'LAVADO'], true)) {
        throw new Exception('Tipo inválido');
    }


    /* =======================
       2) ARCHIVO (OPCIONAL)
    ======================= */
    $archivoPath = null;

    if (
        isset($_FILES['archivo']) &&
        is_array($_FILES['archivo']) &&
        ($_FILES['archivo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
    ) {

        $err = (int)$_FILES['archivo']['error'];
        if ($err !== UPLOAD_ERR_OK) {
            throw new Exception('Error al subir archivo (código ' . $err . ')');
        }

        $tmp = (string)($_FILES['archivo']['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new Exception('Archivo inválido');
        }

        // extensión
        $nameOrig = (string)($_FILES['archivo']['name'] ?? '');
        $ext = strtolower(pathinfo($nameOrig, PATHINFO_EXTENSION));
        $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowedExt, true)) {
            throw new Exception('Formato de imagen no permitido');
        }

        // MIME
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = $finfo ? finfo_file($finfo, $tmp) : null;
            if ($finfo) finfo_close($finfo);

            $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
            if ($mime && !in_array($mime, $allowedMime, true)) {
                throw new Exception('Tipo de imagen no permitido');
            }
        }

        // tamaño
        if ((int)$_FILES['archivo']['size'] > 4 * 1024 * 1024) {
            throw new Exception('Imagen demasiado grande (máx 4MB)');
        }

        // carpeta
        $destDir = __DIR__ . '/../uploads/';
        if (!is_dir($destDir)) {
            mkdir($destDir, 0775, true);
        }

        $safeName = 'doc_' . date('Ymd_His') . '_' . random_int(100, 999) . '.' . $ext;
        $destPath = $destDir . $safeName;

        if (!move_uploaded_file($tmp, $destPath)) {
            throw new Exception('No se pudo guardar el archivo');
        }

        $archivoPath = 'bins/uploads/' . $safeName;
    }

    /* =======================
       3) INSERT MOVIMIENTOS (BATCH)
    ======================= */

    // preparar búsqueda de BIN
    $stmtFind = $mysqliBins->prepare("
    SELECT id
    FROM bins
    WHERE 
        bin_codigo = ?
        OR numero_bin = ?
    LIMIT 1
");

    if (!$stmtFind) {
        throw new Exception('Error interno DB (find bin)');
    }

    // preparar insert
    $stmtInsert = $mysqliBins->prepare("
    INSERT INTO movimientos_bins
    (bin_id, tipo, documento, proveedor, estado_bin, archivo)
    VALUES (?, ?, ?, ?, ?, ?)
");

    if (!$stmtInsert) {
        throw new Exception('Error interno DB (insert)');
    }

    $insertados = 0;

    foreach ($bins as $binCodigo) {

        $binCodigo = trim((string)$binCodigo);
        if ($binCodigo === '') {
            continue;
        }

        // Extraer número del BIN si viene como BIN-833 o Calle1-833
        $numeroBin = null;

        if (preg_match('/BIN-(\d+)/i', $binCodigo, $m)) {
            $numeroBin = (int)$m[1];
        } elseif (preg_match('/-(\d+)$/', $binCodigo, $m)) {
            $numeroBin = (int)$m[1];
        }

        // buscar bin_id
        $stmtFind->bind_param('si', $binCodigo, $numeroBin);
        $stmtFind->execute();
        $stmtFind->bind_result($binId);
        $stmtFind->fetch();
        $stmtFind->free_result();


        if (!$binId) {
            continue; // BIN no existe → se omite
        }

        // insertar movimiento
        $stmtInsert->bind_param(
            'isssss',
            $binId,
            $tipo,
            $documento,
            $proveedor,
            $estadoBin,
            $archivoPath
        );

        $stmtInsert->execute();
        $insertados++;
    }

    $stmtFind->close();
    $stmtInsert->close();

    if ($insertados === 0) {
        throw new Exception('No se pudo registrar ningún BIN');
    }

    respond([
        'ok'  => true,
        'msg' => $insertados . ' BIN registrados correctamente'
    ]);
} catch (Throwable $e) {
    respond([
        'ok'  => false,
        'msg' => $e->getMessage()
    ], 400);
}
