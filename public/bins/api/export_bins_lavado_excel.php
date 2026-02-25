<?php
declare(strict_types=1);

/* ===============================
| HEADERS EXCEL
|=============================== */
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename=lavado_bins.xls');
header('Pragma: no-cache');
header('Expires: 0');

/* ===============================
| Timezone
|=============================== */
date_default_timezone_set('America/Santiago');

/* ===============================
| DB BINS
|=============================== */
require_once __DIR__ . '/../../../config/db_bins.php';

/* ===============================
| Filtros (MISMO PATRÓN list_bins_lavado)
|=============================== */
$filters = [
    'start_date' => $_GET['start_date'] ?? '',
    'end_date'   => $_GET['end_date'] ?? '',
    'bin'        => $_GET['bin'] ?? '',
    'calle'      => $_GET['calle'] ?? '',
    'documento'  => $_GET['documento'] ?? '',
];

/* ===============================
| WHERE BASE (SOLO LAVADO)
|=============================== */
$where  = [];
$params = [];

$where[] = "m.tipo = 'LAVADO'";

/* Fechas */
if ($filters['start_date'] !== '') {
    $where[]  = 'm.fecha >= ?';
    $params[] = $filters['start_date'] . ' 00:00:00';
}
if ($filters['end_date'] !== '') {
    $where[]  = 'm.fecha <= ?';
    $params[] = $filters['end_date'] . ' 23:59:59';
}

/* BIN */
if ($filters['bin'] !== '') {
    $where[]  = '(b.bin_codigo LIKE ? OR CAST(b.numero_bin AS CHAR) LIKE ? OR CAST(b.id AS CHAR) = ?)';
    $params[] = '%' . $filters['bin'] . '%';
    $params[] = '%' . $filters['bin'] . '%';
    $params[] = $filters['bin'];
}

if ($filters['calle'] !== '') {
    $where[]  = 'b.calle LIKE ?';
    $params[] = '%' . $filters['calle'] . '%';
}

if ($filters['documento'] !== '') {
    $where[]  = 'm.documento LIKE ?';
    $params[] = '%' . $filters['documento'] . '%';
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

/* ===============================
| QUERY FINAL
|=============================== */
$sql = "
    SELECT
        m.id,
        m.fecha,
        b.numero_bin,
        m.documento,
        m.proveedor,
        m.estado_bin,
        b.bin_codigo,
        b.calle,
        m.tipo,
        m.archivo
    FROM movimientos_bins m
    INNER JOIN bins b ON b.id = m.bin_id
    $whereSql
    ORDER BY m.fecha DESC
";

$stmt = $mysqliBins->prepare($sql);

if ($params) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$rows   = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* ===============================
| OUTPUT EXCEL
|=============================== */
echo "<table border='1'>";
echo "<tr>
        <th>ID</th>
        <th>Fecha / Hora</th>
        <th>N BIN</th>
        <th>Documento</th>
        <th>Proveedor</th>
        <th>Estado BIN</th>
        <th>BIN</th>
        <th>Calle</th>
        <th>Tipo Movimiento</th>
        <th>Foto</th>
      </tr>";

foreach ($rows as $r) {
    echo "<tr>
            <td>{$r['id']}</td>
            <td>{$r['fecha']}</td>
            <td>{$r['numero_bin']}</td>
            <td>{$r['documento']}</td>
            <td>{$r['proveedor']}</td>
            <td>{$r['estado_bin']}</td>
            <td>{$r['bin_codigo']}</td>
            <td>{$r['calle']}</td>
            <td>{$r['tipo']}</td>
            <td>{$r['archivo']}</td>
          </tr>";
}

echo "</table>";
exit;
