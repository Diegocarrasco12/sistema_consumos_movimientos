<?php
$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Imprimir PDF en Zebra</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .box { max-width: 500px; margin: 0 auto; }
    .ok { background: #e8f7e8; color: #1d6b1d; padding: 10px; margin-bottom: 10px; border-radius: 8px; }
    .err { background: #fdeaea; color: #a11; padding: 10px; margin-bottom: 10px; border-radius: 8px; }
    button { padding: 12px 18px; border: 0; border-radius: 8px; cursor: pointer; }
    input[type=file] { margin-bottom: 12px; width: 100%; }
  </style>
</head>
<body>
  <div class="box">
    <h2>Imprimir PDF en Zebra</h2>

    <?php if ($msg): ?>
      <div class="ok"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <?php if ($err): ?>
      <div class="err"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <form action="imprimir_pdf.php" method="post" enctype="multipart/form-data">
      <input type="file" name="pdf" accept="application/pdf" required>
      <button type="submit">Enviar a impresión</button>
    </form>
  </div>
</body>
</html>