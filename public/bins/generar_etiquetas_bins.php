<?php

declare(strict_types=1);

/**
 * Genera etiquetas finales:
 * - Lee QRs desde /qrs/
 * - Agrega texto "BIN XXX" debajo
 * - Guarda en /qrs_etiquetas/
 */

$srcDir  = __DIR__ . '/qrs/';
$outDir  = __DIR__ . '/qrs_etiquetas/';

if (!is_dir($srcDir) || !is_dir($outDir)) {
    die("❌ Carpeta origen o destino no existe\n");
}

$files = glob($srcDir . '*.png');
$total = 0;

foreach ($files as $file) {

    // Obtener nombre base (BIN-CALLE15-523.png)
    $base = basename($file, '.png');

    // Extraer número del BIN (últimos dígitos)
    if (!preg_match('/(\d+)$/', $base, $m)) {
        continue;
    }
    $numeroBin = $m[1];

    // Crear imagen QR original
    $qrImg = imagecreatefrompng($file);
    if (!$qrImg) continue;

    $qrW = imagesx($qrImg);
    $qrH = imagesy($qrImg);

    // Altura extra para el texto
    $textHeight = 40;

    // Nueva imagen final
    $finalImg = imagecreatetruecolor($qrW, $qrH + $textHeight);

    // Colores
    $white = imagecolorallocate($finalImg, 255, 255, 255);
    $black = imagecolorallocate($finalImg, 0, 0, 0);

    // Fondo blanco
    imagefill($finalImg, 0, 0, $white);

    // Copiar QR
    imagecopy($finalImg, $qrImg, 0, 0, 0, 0, $qrW, $qrH);

    // Texto (FUENTE SEGURA DEL SISTEMA)
    $fontFile = 'C:/Windows/Fonts/arialbd.ttf'; // Arial Bold del sistema
    $text     = 'BIN ' . $numeroBin;
    $fontSize = 36; // MÁS GRANDE y legible

    // Verificación defensiva (opcional pero recomendable)
    if (!file_exists($fontFile)) {
        die('❌ Fuente no encontrada: ' . $fontFile);
    }

    // Calcular centrado
    $bbox = imagettfbbox($fontSize, 0, $fontFile, $text);
    $textWidth = $bbox[2] - $bbox[0];
    $x = (int)(($qrW - $textWidth) / 2);
    $y = $qrH + 38; // un poco más abajo para que respire

    imagettftext(
        $finalImg,
        $fontSize,
        0,
        $x,
        $y,
        $black,
        $fontFile,
        $text
    );


    // Guardar
    $outPath = $outDir . $base . '.png';
    imagepng($finalImg, $outPath);

    // Limpiar
    imagedestroy($qrImg);
    imagedestroy($finalImg);

    $total++;
}

echo "✅ Etiquetas generadas: {$total}\n";
echo "📁 Carpeta: {$outDir}\n";
