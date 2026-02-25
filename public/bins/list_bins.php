<?php

declare(strict_types=1);

/* ===============================
| DEBUG (solo mientras pruebas)
|=============================== */
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

/* ===============================
| Timezone
|=============================== */
date_default_timezone_set('America/Santiago');

/* ===============================
| DB (helpers + conexión base)
|=============================== */
require_once __DIR__ . '/../../config/db_bins.php';
function db_select_bins(string $sql, array $params = []): array
{
    global $mysqliBins;

    $stmt = $mysqliBins->prepare($sql);
    if (!$stmt) {
        die('SQL prepare error');
    }

    if ($params) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}



/* ===============================
| Helper XSS
|=============================== */
function h($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/* ===============================
| Filtros
|=============================== */
$filters = [
    'start_date' => $_GET['start_date'] ?? '',
    'end_date'   => $_GET['end_date'] ?? '',
    'bin'        => $_GET['bin'] ?? '',
    'calle'      => $_GET['calle'] ?? '',
    'tipo'       => $_GET['tipo'] ?? '',
    'documento'  => $_GET['documento'] ?? '',
];

/* ===============================
| Paginación
|=============================== */
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 18;
$offset = ($page - 1) * $limit;

/* ===============================
| WHERE dinámico
|=============================== */
$where  = [];
$params = [];
// ⛔ EXCLUIR LAVADO DEL LISTADO NORMAL
$where[]  = "m.tipo IN ('ENTRADA','SALIDA')";


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

if ($filters['tipo'] !== '' && in_array($filters['tipo'], ['ENTRADA','SALIDA'], true)) {
    $where[]  = 'm.tipo = ?';
    $params[] = $filters['tipo'];
}


if ($filters['documento'] !== '') {
    $where[]  = 'm.documento LIKE ?';
    $params[] = '%' . $filters['documento'] . '%';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* ===============================
| Total registros
|=============================== */
$sqlCount = "
    SELECT COUNT(*) AS total
    FROM control_bins.movimientos_bins m
    INNER JOIN control_bins.bins b ON b.id = m.bin_id
    $whereSql
";

$resCount = db_select_bins($sqlCount, $params);
$totalRows  = (int)($resCount[0]['total'] ?? 0);
$totalPages = (int)ceil($totalRows / $limit);

/* ===============================
| Datos (ordenados según vista)
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

    FROM control_bins.movimientos_bins m
    INNER JOIN control_bins.bins b ON b.id = m.bin_id
    $whereSql
    ORDER BY m.fecha DESC
    LIMIT $limit OFFSET $offset
";

$rows     = db_select_bins($sql, $params);

/* ===============================
| Helper paginación
|=============================== */
function buildUrl(array $filters, int $page): string
{
    $filters['page'] = $page;
    return 'list_bins.php?' . http_build_query($filters);
}
?>
<?php
$currentUrl = $_SERVER['REQUEST_URI'];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Movimientos BINS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../styles.css?v=20260109" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container-fluid py-4">

        <h1 class="mb-3">📋 Movimientos BINS</h1>

        <!-- FILTROS -->
        <form method="get" class="card card-body shadow-sm mb-3">
            <div class="row g-3 align-items-end">

                <div class="col-md-2">
                    <label class="form-label">Desde</label>
                    <input type="date" name="start_date" class="form-control" value="<?= h($filters['start_date']) ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="end_date" class="form-control" value="<?= h($filters['end_date']) ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label">BIN / Nº / ID</label>
                    <input type="text" name="bin" class="form-control" value="<?= h($filters['bin']) ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Calle</label>
                    <input type="text" name="calle" class="form-control" value="<?= h($filters['calle']) ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Tipo</label>
                    <select name="tipo" class="form-select">
                        <option value="">Todos</option>
                        <option value="ENTRADA" <?= $filters['tipo'] === 'ENTRADA' ? 'selected' : '' ?>>ENTRADA</option>
                        <option value="SALIDA" <?= $filters['tipo'] === 'SALIDA' ? 'selected' : '' ?>>SALIDA</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Documento</label>
                    <input type="text" name="documento" class="form-control" value="<?= h($filters['documento']) ?>">
                </div>

                <div class="col-12">
                    <button class="btn btn-primary">Filtrar</button>
                    <a href="list_bins.php" class="btn btn-secondary ms-2">Limpiar</a>
                    <span class="ms-3 text-muted">Total: <?= $totalRows ?></span>
                </div>
            </div>
        </form>

        <!-- ACCIONES -->
        <div class="mb-3">
            <a href="index.php" class="btn btn-success">➕ Nuevo movimiento</a>
            <a href="api/export_bins_excel.php?<?= http_build_query($filters) ?>" class="btn btn-outline-secondary ms-2">
                📥 Exportar Excel
            </a>
        </div>

        <!-- TABLA -->
        <form method="post"
            action="api/save_bins_bulk.php"
            enctype="multipart/form-data">
            <input type="hidden" name="return_url" value="<?= h($currentUrl) ?>">

            <button type="submit" class="btn btn-warning mb-3">
                💾 Guardar cambios
            </button>

            <div class="table-responsive">
                <table class="table table-striped table-bordered table-sm align-middle">

                    <thead class="table-dark">
                        <tr>
                            <th class="text-end">ID</th>
                            <th>Fecha / Hora</th>
                            <th class="text-end">Nº BIN</th>
                            <th>Documento</th>
                            <th>Proveedor</th>
                            <th>Estado BIN</th>
                            <th>BIN</th>
                            <th>Calle</th>
                            <th>Tipo Movimiento</th>
                            <th>Foto</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <?php
                            $fileUrl = '';
                            if (!empty($r['archivo'])) {
                                $fileUrl = '../' . ltrim((string)$r['archivo'], '/');
                            }
                            ?>
                            <tr>
                                <td class="text-end">
                                    <?= (int)$r['id'] ?>
                                    <input type="hidden"
                                        name="rows[<?= (int)$r['id'] ?>][id]"
                                        value="<?= (int)$r['id'] ?>">
                                </td>

                                <td><?= h(date('d-m-Y H:i:s', strtotime((string)$r['fecha']))) ?></td>

                                <td class="text-end"><?= number_format((int)$r['numero_bin'], 0, ',', '.') ?></td>

                                <td>
                                    <input class="form-control form-control-sm"
                                        name="rows[<?= (int)$r['id'] ?>][documento]"
                                        value="<?= h($r['documento']) ?>">
                                </td>


                                <td>
                                    <span class="badge text-bg-secondary">
                                        <?= h($r['proveedor']) ?>
                                    </span>
                                </td>

                                <td>
                                    <input class="form-control form-control-sm"
                                        name="rows[<?= (int)$r['id'] ?>][estado_bin]"
                                        value="<?= h($r['estado_bin']) ?>">
                                </td>

                                <td><strong><?= h($r['bin_codigo']) ?></strong></td>

                                <td><?= h($r['calle']) ?></td>

                                <td>
                                    <span class="badge <?= $r['tipo'] === 'ENTRADA' ? 'text-bg-success' : 'text-bg-danger' ?>">

                                        <?= h($r['tipo']) ?>
                                    </span>
                                </td>

                                <td class="text-center">

                                    <?php if (!empty($r['archivo'])): ?>
                                        <a class="btn btn-sm btn-outline-primary mb-1"
                                            target="_blank"
                                            href="<?= h('../' . ltrim((string)$r['archivo'], '/')) ?>">
                                            Ver
                                        </a>
                                    <?php endif; ?>

                                    <input type="file"
                                        name="files[<?= (int)$r['id'] ?>]"
                                        accept=".jpg,.jpeg,.png,.pdf"
                                        class="form-control form-control-sm">

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        <?php if (!$rows): ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    No hay registros con esos filtros.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>


        <?php if ($totalPages > 1): ?>
            <nav class="mt-3">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $page > 1 ? buildUrl($filters, $page - 1) : '#' ?>">« Anterior</a>
                    </li>

                    <?php
                    $start = max(1, $page - 2);
                    $end   = min($totalPages, $page + 2);
                    ?>

                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= buildUrl($filters, $i) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $page < $totalPages ? buildUrl($filters, $page + 1) : '#' ?>">Siguiente »</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>

    </div>
    <script>
        document.addEventListener('paste', function(e) {
            const input = e.target;

            // Solo inputs
            if (input.tagName !== 'INPUT') return;

            const td = input.closest('td');
            if (!td) return;

            const text = e.clipboardData.getData('text');

            // Solo actuar si hay múltiples líneas
            if (!text.includes('\n')) return;

            e.preventDefault();

            const values = text.replace(/\r/g, '').split('\n');

            let row = input.closest('tr');
            const colIndex = td.cellIndex;

            values.forEach(value => {
                if (!row) return;

                const cell = row.cells[colIndex];
                if (!cell) return;

                const targetInput = cell.querySelector('input');
                if (targetInput) {
                    targetInput.value = value;
                }

                row = row.nextElementSibling;
            });
        });
    </script>
   <script>
  // === GUARDAR SCROLL ANTES DE GUARDAR ===
  const form = document.querySelector('form[action="api/save_bins_bulk.php"]');

  if (form) {
    form.addEventListener('submit', () => {
      sessionStorage.setItem('bins_scroll_y', window.scrollY.toString());
    });
  }

  // === RESTAURAR SCROLL (POST-RENDER REAL) ===
  document.addEventListener('DOMContentLoaded', () => {
    const scrollY = sessionStorage.getItem('bins_scroll_y');

    if (scrollY !== null) {
      // Espera a que el layout termine realmente
      requestAnimationFrame(() => {
        requestAnimationFrame(() => {
          window.scrollTo(0, parseInt(scrollY, 10));
          sessionStorage.removeItem('bins_scroll_y');
        });
      });
    }
  });
</script>

</body>

</html>