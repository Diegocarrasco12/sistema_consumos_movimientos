<?php
require __DIR__ . '/config.php';

function back($okMsg = null, $errMsg = null) {
  $q = [];
  if ($okMsg) $q['msg'] = $okMsg;
  if ($errMsg) $q['err'] = $errMsg;
  header('Location: index.php?' . http_build_query($q));
  exit;
}

$bin = trim($_POST['bin'] ?? '');

// Validación
if ($bin === '') {
  back(null, 'Ingresa el número de bin.');
}
if (!preg_match('/^[0-9]+$/', $bin)) {
  back(null, 'BIN inválido. Solo números.');
}

$binCode = "BIN-$bin";
$qrUrl = QR_BASE_URL . $binCode;

// ZPL (igual que antes, solo cambia el contenido del bin)
$zpl = "^XA\n"
     . "^PW1160\n"
     . "^LL1344\n"
     . "^LH0,0\n"
     . "^FO195,120\n"
     . "^BQN,2,23\n"
     . "^FDLA," . $qrUrl . "^FS\n"
     . "^FO420,960\n"
     . "^A0N,120,120\n"
     . "^FDBIN $bin^FS\n"
     . "^XZ\n";

// Envío a Zebra
$errno = 0; 
$errstr = '';
$socket = @fsockopen(ZEBRA_IP, ZEBRA_PORT, $errno, $errstr, 3);

if (!$socket) {
  back(null, "No se pudo conectar a la impresora ($errstr)");
}

stream_set_timeout($socket, 3);
fwrite($socket, $zpl);
fclose($socket);

back("Impresión enviada: $binCode");
