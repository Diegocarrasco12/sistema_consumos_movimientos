<?php

declare(strict_types=1);

/**
 * Genera 1 QR PNG por BIN usando bin_codigo
 * - Lee desde DB control_bins (usa tu config/db_bins.php)
 * - Guarda en /bins/qrs/
 * - Contenido del QR: URL scan.php?bin=<bin_codigo>
 */

require_once __DIR__ . '/../../config/db_bins.php';
require_once __DIR__ . '/../../lib/phpqrcode/qrlib.php';

// 1) Carpeta salida (ya la creaste)
$outDir = __DIR__ . '/qrs/';
if (!is_dir($outDir)) {
    die("❌ No existe la carpeta destino: {$outDir}\n");
}

// 2) Base URL (LOCAL vs PRODUCCIÓN)
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

if (stripos($host, 'localhost') !== false || $host === '127.0.0.1') {
    $baseUrl = 'http://localhost/consumo_papel/bins/scan.php?bin=';
} else {
    $baseUrl = 'https://consumo_papel.faret.cl/bins/scan.php?bin=';
}

// 3) Tomar BINs desde DB
$sql = "SELECT bin_codigo, numero_bin FROM bins WHERE activo = 1 ORDER BY calle, numero_bin";
$stmt = $pdoBins->query($sql);

$total = 0;

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $binCodigo = $row['bin_codigo'];
    $numeroBin = (string)$row['numero_bin'];

    // URL que irá dentro del QR
    $qrValue = $baseUrl . urlencode($binCodigo);

    // Nombre del archivo (sin caracteres raros)
    $safeName = preg_replace('/[^A-Za-z0-9\-_]/', '_', $binCodigo);
    $pngPath  = $outDir . $safeName . '.png';

    // Generar QR (alto nivel de corrección, tamaño razonable)
    QRcode::png(
        $qrValue,
        $pngPath,
        'Q',
        8,
        2
    );


    $total++;
}

echo "✅ Listo. QRs generados: {$total}\n";
echo "📁 Carpeta: {$outDir}\n";
echo "🔗 Base URL: {$baseUrl}\n";
