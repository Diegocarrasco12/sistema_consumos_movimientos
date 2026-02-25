<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/db.php';
require_once '../../config/db_sap.php';
$sap = db_sap_connect();

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


function h($v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

/* ===============================
| Filtros
|=============================== */
$filters = [
    'start_date' => $_GET['start_date'] ?? '',
    'end_date'   => $_GET['end_date'] ?? '',
    'lote'       => $_GET['lote'] ?? '',
    'planta'     => $_GET['planta'] ?? '',
    'tipo'       => $_GET['tipo'] ?? '',
];

/* ===============================
| WHERE dinámico
|=============================== */
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
| Datos
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
    LIMIT 500
", $params);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Movimientos PALETS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="../styles.css?v=20260109">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container-fluid py-4">

        <h1 class="mb-3">📋 Movimientos PALETS</h1>

        <!-- FILTROS -->
        <form method="get" class="card card-body shadow-sm mb-3">
            <div class="row g-3 align-items-end">

                <div class="col-md-2">
                    <label class="form-label">Desde</label>
                    <input type="date" name="start_date" class="form-control"
                        value="<?= h($filters['start_date']) ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="end_date" class="form-control"
                        value="<?= h($filters['end_date']) ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Lote</label>
                    <input type="text" name="lote" class="form-control"
                        value="<?= h($filters['lote']) ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Planta</label>
                    <select name="planta" class="form-select">
                        <option value="">Todas</option>
                        <option value="INNPACK" <?= $filters['planta'] === 'INNPACK' ? 'selected' : '' ?>>INNPACK</option>
                        <option value="FARET" <?= $filters['planta'] === 'FARET' ? 'selected' : '' ?>>FARET</option>
                        <option value="SFM" <?= $filters['planta'] === 'SFM' ? 'selected' : '' ?>>SFM</option>
                        <option value="EUROPA" <?= $filters['planta'] === 'EUROPA' ? 'selected' : '' ?>>EUROPA</option>
                        <option value="DOMINGO ARTEAGA" <?= $filters['planta'] === 'DOMINGO ARTEAGA' ? 'selected' : '' ?>>DOMINGO ARTEAGA</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Movimiento</label>
                    <select name="tipo" class="form-select">
                        <option value="">Todos</option>
                        <option value="ENTRADA" <?= $filters['tipo'] === 'ENTRADA' ? 'selected' : '' ?>>ENTRADA</option>
                        <option value="SALIDA" <?= $filters['tipo'] === 'SALIDA' ? 'selected' : '' ?>>SALIDA</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <button class="btn btn-primary w-100">Filtrar</button>
                </div>

                <div class="col-md-2">
                    <a href="historial.php" class="btn btn-secondary w-100">
                        Limpiar
                    </a>
                </div>

            </div>
        </form>

        <!-- ACCIONES -->
        <div class="mb-3 d-flex gap-2">
            <a href="index.php" class="btn btn-success">
                📷 Nuevo escaneo
            </a>

            <a href="export_palets_excel.php?<?= http_build_query($filters) ?>"
                class="btn btn-outline-primary">
                📊 Exportar Excel
            </a>
        </div>


        <!-- TABLA -->
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-sm align-middle">

                <thead class="table-dark">
                    <tr>
                        <th class="text-end">ID</th>
                        <th>Fecha</th>
                        <th>Lote</th>
                        <th>Codigo</th>
                        <th>Detalle</th>
                        <th>Planta</th>
                        <th>Movimiento</th>
                        <th class="text-end">Cantidad</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <?php
                        $detalle = obtener_detalle_sap($sap, $r['ean13']);

                        ?>
                        <tr>
                            <td class="text-end"><?= (int)$r['id'] ?></td>
                            <td><?= h(date('d-m-Y H:i:s', strtotime((string)$r['fecha']))) ?></td>
                            <td><strong><?= h($r['lote']) ?></strong></td>
                            <td><?= h($r['ean13']) ?></td>
                            <td class="text-muted"><?= h($detalle) ?></td>

                            <td><?= h($r['planta']) ?></td>
                            <td>
                                <span class="badge <?= $r['tipo_movimiento'] === 'ENTRADA' ? 'text-bg-success' : 'text-bg-danger' ?>">
                                    <?= h($r['tipo_movimiento']) ?>
                                </span>
                            </td>
                            <td class="text-end"><?= h($r['cantidad']) ?></td>

                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$rows): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                No hay registros con esos filtros.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>

            </table>
        </div>

    </div>
</body>

</html>