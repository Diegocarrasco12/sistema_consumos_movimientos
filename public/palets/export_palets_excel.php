<?php

declare(strict_types=1);

/* ===============================
| HEADERS EXCEL
|=============================== */
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename=movimientos_palets.xls');
header('Pragma: no-cache');
header('Expires: 0');

/* ===============================
| Timezone
|=============================== */
date_default_timezone_set('America/Santiago');

/* ===============================
| DB LOCAL
|=============================== */
require_once dirname(__DIR__, 2) . '/config/db.php';
require_once '../../config/db_sap.php';
$sap = db_sap_connect();

/* ===============================
| Función SAP
|=============================== */
function obtener_detalle_sap($sap, $ean)
{
    $sql = "
        SELECT TOP 1
            ItemName
        FROM ZZZProcesosProductivos
        WHERE ItemCode = :ean
    ";

    $stmt = $sap->prepare($sql);
    $stmt->execute(['ean' => $ean]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && !empty($row['ItemName'])) {
        return $row['ItemName'];
    }

    return "No encontrado";
}

/* ===============================
| Filtros (MISMO PATRÓN)
|=============================== */
$filters = [
    'start_date' => $_GET['start_date'] ?? '',
    'end_date'   => $_GET['end_date'] ?? '',
    'lote'       => $_GET['lote'] ?? '',
    'planta'     => $_GET['planta'] ?? '',
    'tipo'       => $_GET['tipo'] ?? '',
];

$where  = [];
$params = [];

/* Fechas */
if ($filters['start_date'] !== '') {
    $where[]  = 'fecha >= ?';
    $params[] = $filters['start_date'] . ' 00:00:00';
}

if ($filters['end_date'] !== '') {
    $where[]  = 'fecha <= ?';
    $params[] = $filters['end_date'] . ' 23:59:59';
}

/* Lote */
if ($filters['lote'] !== '') {
    $where[]  = 'lote LIKE ?';
    $params[] = '%' . $filters['lote'] . '%';
}

/* Planta */
if ($filters['planta'] !== '') {
    $where[]  = 'planta = ?';
    $params[] = $filters['planta'];
}

/* Tipo */
if ($filters['tipo'] !== '') {
    $where[]  = 'tipo_movimiento = ?';
    $params[] = $filters['tipo'];
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* ===============================
| QUERY PALLETS
|=============================== */
$rows = db_select("
    SELECT
        id,
        fecha,
        planta,
        tipo_movimiento,
        ean13,
        cantidad,
        lote
    FROM palets_movimientos
    $whereSql
    ORDER BY fecha DESC
", $params);

/* ===============================
| OUTPUT EXCEL
|=============================== */
echo "
<html>
<head>
<meta charset='UTF-8'>
<style>
table {
    border-collapse: collapse;
    font-family: Arial, sans-serif;
    font-size: 12px;
}
th {
    background: #1f2937;
    color: #ffffff;
    padding: 6px;
    border: 1px solid #000;
    text-align: center;
}
td {
    padding: 5px;
    border: 1px solid #000;
}
.text-right {
    text-align: right;
}
.text-center {
    text-align: center;
}
</style>
</head>
<body>
<table>
<tr>
    <th>ID</th>
    <th>Fecha</th>
    <th>Lote</th>
    <th>Codigo</th>
    <th>Detalle</th>
    <th>Planta</th>
    <th>Movimiento</th>
    <th>Cantidad</th>
</tr>
";


foreach ($rows as $r) {

    $detalle = obtener_detalle_sap($sap, $r['ean13']);

    echo "<tr>
            <td class='text-right'>{$r['id']}</td>
            <td>{$r['fecha']}</td>
            <td>{$r['lote']}</td>
            <td>{$r['ean13']}</td>
            <td>{$detalle}</td>
            <td>{$r['planta']}</td>
            <td class='text-center'>{$r['tipo_movimiento']}</td>
            <td class='text-right'>{$r['cantidad']}</td>
          </tr>";
}



echo "
</table>
</body>
</html>
";
exit;

