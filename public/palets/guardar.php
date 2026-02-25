<?php
declare(strict_types=1);

// Debug real
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Conexión del sistema
require_once dirname(__DIR__, 2) . '/config/db.php';

// Parser QR
require_once __DIR__ . '/gs1_parser.php';

// ===============================
// Datos POST
// ===============================
$qr     = trim($_POST['qr'] ?? '');
$planta = trim($_POST['planta'] ?? '');
$tipo   = trim($_POST['tipo'] ?? '');

// Validación básica
if ($qr === '' || $planta === '' || !in_array($tipo, ['ENTRADA','SALIDA'], true)) {
    die("Datos inválidos.");
}

// ===============================
// Parseo QR
// ===============================
$parsed = parse_qr_payload($qr);

// Código de palet
$codigo_palet =
    $parsed['lote']
    ?: ($parsed['nv']
    ?: substr(sha1($parsed['qr_raw']), 0, 12));

// Asegurar valores no nulos
$qr_raw   = $parsed['qr_raw']   ?? '';
$gtin14   = $parsed['gtin14']   ?? '';
$ean13    = $parsed['ean13']    ?? '';
$cantidad = $parsed['cantidad'] ?? 0;
$lote     = $parsed['lote']     ?? '';
$nv       = $parsed['nv']       ?? '';

// ===============================
// INSERT
// ===============================
$sql = "
INSERT INTO palets_movimientos
(codigo_palet, planta, tipo_movimiento, fecha, qr_raw, gtin14, ean13, cantidad, lote, nv)
VALUES
(?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?)
";

$params = [
    $codigo_palet,
    $planta,
    $tipo,
    $qr_raw,
    $gtin14,
    $ean13,
    (int)$cantidad,
    $lote,
    $nv
];

$ok = db_exec($sql, $params);

// ===============================
// Verificación
// ===============================
if ($ok <= 0) {
    die("Error al guardar movimiento. Revisar estructura o parser.");
}

// Redirección limpia
header("Location: index.php?ok=1");
exit;
