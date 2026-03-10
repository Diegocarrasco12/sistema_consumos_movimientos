<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/zebra_print.php';

function zebraSendRaw(string $data): array
{
    $errno = 0;
    $errstr = '';

    $socket = @fsockopen(ZEBRA_IP, (int)ZEBRA_PORT, $errno, $errstr, ZEBRA_TIMEOUT);

    if (!$socket) {
        return [
            'ok' => false,
            'message' => "No se pudo conectar a la Zebra: $errstr ($errno)"
        ];
    }

    stream_set_timeout($socket, ZEBRA_TIMEOUT);

    $written = fwrite($socket, $data);
    fclose($socket);

    if ($written === false) {
        return [
            'ok' => false,
            'message' => 'No se pudo enviar información a la impresora.'
        ];
    }

    return [
        'ok' => true,
        'message' => 'Impresión enviada correctamente.'
    ];
}

function zebraPrintPdfFile(string $pdfPath): array
{
    if (!file_exists($pdfPath)) {
        return [
            'ok' => false,
            'message' => 'El archivo PDF no existe.'
        ];
    }

    $pdfData = file_get_contents($pdfPath);

    if ($pdfData === false || $pdfData === '') {
        return [
            'ok' => false,
            'message' => 'No se pudo leer el PDF.'
        ];
    }

    return zebraSendRaw($pdfData);
}