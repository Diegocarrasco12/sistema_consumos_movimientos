<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/qr_parser.php';
require_once __DIR__ . '/../models/TarjaScan.php';

use function Helpers\parse_qr;
use Models\TarjaScan;

$message = '';
$errors  = [];

$raw_qr         = $_POST['raw_qr'] ?? '';
$np             = $_POST['np'] ?? '';
$saldo_in       = $_POST['saldo_kg'] ?? '';
$descripcion_in = $_POST['descripcion'] ?? '';
$codigo_in      = $_POST['codigo'] ?? '';
$tarja_in       = $_POST['tarja_kg'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $raw_qr         = trim((string)$raw_qr);
    $np             = trim((string)$np);
    $descripcion_in = trim((string)$descripcion_in);
    $codigo_in      = strtoupper(trim((string)$codigo_in));

    $saldo_kg = ($saldo_in !== '' && $saldo_in !== null)
        ? (float) str_replace(',', '.', (string)$saldo_in)
        : 0.0;

    $tarja_kg = ($tarja_in !== '' && $tarja_in !== null)
        ? (float) str_replace(',', '.', (string)$tarja_in)
        : 0.0;

    if ($raw_qr === '') {
        $errors[] = 'Debe escanear un código QR.';
    }

    if ($codigo_in === '') {
        $errors[] = 'El código del producto es obligatorio.';
    }

    if ($descripcion_in === '') {
        $errors[] = 'La descripción del producto es obligatoria.';
    }

    if ($tarja_kg <= 0) {
        $errors[] = 'Tarja KG inválida.';
    }

    if (empty($errors)) {

        $parsed = parse_qr($raw_qr);
        $lote = isset($parsed['lote']) ? trim((string)$parsed['lote']) : '';

        if ($lote === '') {
            $errors[] = 'No se pudo obtener el lote desde el QR.';
        }

        if (empty($errors)) {

            $saldo_kg = max(0.0, (float)$saldo_kg);
            $consumo_kg = $tarja_kg - $saldo_kg;

            if ($consumo_kg < 0) {
                $consumo_kg = 0;
                $errors[] = 'El SALDO KG no puede ser mayor que la tarja.';
            }

            if (empty($errors)) {

                TarjaScan::create([
                    'descripcion' => $descripcion_in,
                    'codigo'      => $codigo_in,
                    'consumo_kg'  => $consumo_kg,
                    'np'          => $np,
                    'tarja_kg'    => $tarja_kg,
                    'saldo_kg'    => $saldo_kg,
                    'lote'        => $lote,
                    'estado'      => null,
                    'salida'      => null,
                    'raw_qr'      => $raw_qr,
                    'id_usuario'  => null,
                ]);

                $message = 'Registro guardado correctamente.';

                $raw_qr         = '';
                $np             = '';
                $saldo_in       = '';
                $tarja_in       = '';
                $descripcion_in = '';
                $codigo_in      = '';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<title>Lectura de Tarjas QR</title>

<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="styles.css?v=20251105" rel="stylesheet">

</head>

<body class="bg-light">

<div class="container py-4">

<h1 class="mb-4">CONSUMO PAPEL QR</h1>

<?php if ($message): ?>
<div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
<ul class="mb-0">
<?php foreach ($errors as $error): ?>
<li><?php echo htmlspecialchars($error); ?></li>
<?php endforeach; ?>
</ul>
</div>
<?php endif; ?>

<form method="post" class="card card-body shadow-sm">

<div class="mb-3">

<label class="form-label"><strong>Escaneo de Código QR</strong></label>

<div class="p-3 scan-wrap">

<button type="button" id="btnToggleScan" class="btn btn-primary btn-scan">
▶️ Iniciar escaneo
</button>

<div class="mt-3">
<video id="qrVideo" playsinline muted class="d-none"></video>
<canvas id="qrCanvas" class="d-none"></canvas>
</div>

<div class="mt-3">

<div class="muted">Resultado del último QR:</div>

<div id="scanText" class="scan-result text-break small border rounded p-2 bg-white">
—
</div>

<div id="producto-info" class="mt-2"></div>

</div>

</div>

</div>

<textarea id="raw_qr" name="raw_qr" class="d-none"><?php echo htmlspecialchars($raw_qr); ?></textarea>

<!-- TARJA REAL ENVIADA AL BACKEND -->
<input type="hidden" id="tarja_kg" name="tarja_kg">

<div class="mb-3">
<label for="np" class="form-label"><strong>NP</strong></label>
<input type="text" id="np" name="np" class="form-control"
value="<?php echo htmlspecialchars($np); ?>">
</div>

<div class="mb-3">
<label for="tarja_visible" class="form-label"><strong>TARJA KG</strong></label>

<input type="number"
step="0.01"
id="tarja_visible"
class="form-control"
readonly
placeholder="Se completa automáticamente desde SAP">
</div>

<div class="mb-3">
<label for="saldo_kg" class="form-label"><strong>SALDO KG</strong></label>

<input type="number"
step="0.01"
id="saldo_kg"
name="saldo_kg"
class="form-control"
value="<?php echo htmlspecialchars((string)$saldo_in); ?>"
placeholder="0,00">
</div>

<div class="mb-3">
<label for="descripcion" class="form-label"><strong>Descripción</strong></label>

<textarea
id="descripcion"
name="descripcion"
class="form-control"
rows="2"><?php echo htmlspecialchars($descripcion_in); ?></textarea>
</div>

<div class="mb-3">
<label for="codigo" class="form-label"><strong>Código</strong></label>

<input type="text"
id="codigo"
name="codigo"
class="form-control"
value="<?php echo htmlspecialchars($codigo_in); ?>">
</div>

<button type="submit" class="btn btn-primary">Guardar Registro</button>

<a href="list.php" class="btn btn-secondary ms-2">
Ver Registros
</a>

</form>

</div>

<script src="https://unpkg.com/jsqr@1.4.0/dist/jsQR.js"></script>
<script src="qr_scan.js?v=20260310_2"></script>

</body>
</html>