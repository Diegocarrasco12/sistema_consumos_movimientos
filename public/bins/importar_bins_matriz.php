<?php
declare(strict_types=1);

/**
 * IMPORTADOR MATRIZ BINS (PRODUCCIÓN/LOCAL)
 * - DB: control_bins
 * - mysqli only
 * - Lee /data/bins_matriz.csv
 * - Detecta delimitador automáticamente (, ; TAB |)
 * - Inserta: bin_codigo, calle, numero_bin, activo
 */

header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/../../config/db_bins.php';

if (!isset($dbBins) || !($dbBins instanceof mysqli)) {
    die("❌ Conexión DB BINS no disponible\n");
}
if ($dbBins->connect_errno) {
    die("❌ Error conexión MySQL BINS: {$dbBins->connect_error}\n");
}
$dbBins->set_charset('utf8mb4');

// Info útil para no discutir nunca más “qué DB está usando”
$hostInfo = $dbBins->host_info ?? 'unknown';
echo "DB USADA POR PHP: control_bins | HOST: {$hostInfo}\n\n";

// Validar tabla
$check = $dbBins->query("SHOW TABLES LIKE 'bins'");
if (!$check || $check->num_rows === 0) {
    die("❌ La tabla 'bins' no existe en control_bins\n");
}

// Ruta CSV
$csvPath = __DIR__ . '/../../data/bins_matriz.csv';
if (!is_file($csvPath)) {
    die("❌ No se encuentra el CSV: {$csvPath}\n");
}

$fh = fopen($csvPath, 'rb');
if (!$fh) {
    die("❌ No se pudo abrir el CSV\n");
}

// ----- Detectar delimitador con la primera línea -----
$firstLine = fgets($fh);
if ($firstLine === false) {
    fclose($fh);
    die("❌ CSV vacío\n");
}

// Quitar BOM UTF-8 si existe
$firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine);

// Candidatos de delimitador
$delims = ["," , ";" , "\t", "|"];
$bestDelim = ",";
$bestCount = 0;

foreach ($delims as $d) {
    $parts = str_getcsv(rtrim($firstLine, "\r\n"), $d);
    if (count($parts) > $bestCount) {
        $bestCount = count($parts);
        $bestDelim = $d;
    }
}

if ($bestCount < 2) {
    fclose($fh);
    die("❌ No se pudo detectar delimitador (la cabecera se ve como 1 sola columna)\n");
}

echo "✅ Delimitador detectado: " . ($bestDelim === "\t" ? "TAB" : $bestDelim) . "\n";
echo "✅ Columnas detectadas en cabecera: {$bestCount}\n\n";

// Rebobinar para leer con fgetcsv desde el inicio
rewind($fh);

// Leer headers con delimitador detectado
$headers = fgetcsv($fh, 0, $bestDelim);
if (!$headers) {
    fclose($fh);
    die("❌ CSV sin encabezados\n");
}

// Limpiar headers
$headers = array_map(static function($h) {
    $h = preg_replace('/^\xEF\xBB\xBF/', '', (string)$h);
    return trim($h);
}, $headers);

// Preparar statement (incluye bin_codigo)
$sql = "
INSERT INTO bins (bin_codigo, calle, numero_bin, activo)
VALUES (?, ?, ?, 1)
ON DUPLICATE KEY UPDATE
  calle = VALUES(calle),
  numero_bin = VALUES(numero_bin),
  activo = 1
";

$stmt = $dbBins->prepare($sql);
if (!$stmt) {
    fclose($fh);
    die("❌ Error prepare(): {$dbBins->error}\n");
}

// Transacción
$dbBins->begin_transaction();

$insertados = 0;
$ignorados  = 0;
$filas      = 0;

try {
    while (($row = fgetcsv($fh, 0, $bestDelim)) !== false) {
        $filas++;

        // Si la fila viene con menos/más columnas, igual la procesamos hasta lo que exista
        $max = max(count($headers), count($row));

        for ($i = 0; $i < $max; $i++) {

            $calle = $headers[$i] ?? '';
            $val   = $row[$i] ?? '';

            $calle = trim((string)$calle);
            $val   = trim((string)$val);

            if ($calle === '' || $val === '') {
                $ignorados++;
                continue;
            }

            // Extraer número (por si Excel mete espacios)
            $numeroLimpio = preg_replace('/\D+/', '', $val);
            if ($numeroLimpio === '') {
                $ignorados++;
                continue;
            }

            $numero = (int)$numeroLimpio;

            // Rango razonable (ajústalo si necesitas)
            if ($numero < 1 || $numero > 5000) {
                $ignorados++;
                continue;
            }

            // bin_codigo: Calle X - 995 (simple y único)
            $binCodigo = $calle . '-' . $numero;

            $stmt->bind_param('ssi', $binCodigo, $calle, $numero);

            if (!$stmt->execute()) {
                throw new RuntimeException("SQL error: " . $stmt->error . " | bin_codigo={$binCodigo}");
            }

            $insertados++;
        }
    }

    $dbBins->commit();

} catch (Throwable $e) {
    $dbBins->rollback();
    $stmt->close();
    fclose($fh);
    die("❌ Error durante importación: {$e->getMessage()}\n");
}

$stmt->close();
fclose($fh);

// Totales
$res = $dbBins->query("SELECT COUNT(*) AS total FROM bins");
$total = ($res && ($r = $res->fetch_assoc())) ? (int)$r['total'] : 0;

echo "✅ IMPORTACIÓN OK\n";
echo "Filas leídas (data): {$filas}\n";
echo "Insertados/actualizados: {$insertados}\n";
echo "Ignorados: {$ignorados}\n";
echo "Total bins en DB: {$total}\n";
