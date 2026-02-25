<?php
$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Impresión QR BIN</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
:root {
    --primary: #1f7a1f;
    --primary-dark: #166116;
    --bg: #f3f5f7;
    --card: #ffffff;
    --text: #1e293b;
    --muted: #64748b;
    --ok-bg: #e7f7e7;
    --ok-border: #7bc47b;
    --err-bg: #ffecec;
    --err-border: #ff9a9a;
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: "Segoe UI", Roboto, Arial, sans-serif;
}

body {
    background: linear-gradient(135deg, #e9eef2, #f8fafc);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.container {
    width: 100%;
    max-width: 420px;
}

.card {
    background: var(--card);
    border-radius: 14px;
    padding: 32px 26px;
    box-shadow: 0 12px 28px rgba(0,0,0,0.08);
    text-align: center;
}

.logo {
    font-size: 18px;
    font-weight: 600;
    color: var(--muted);
    margin-bottom: 10px;
}

h1 {
    font-size: 24px;
    color: var(--text);
    margin-bottom: 6px;
}

.subtitle {
    font-size: 14px;
    color: var(--muted);
    margin-bottom: 26px;
}

label {
    display: block;
    text-align: left;
    font-weight: 600;
    margin-bottom: 6px;
    color: var(--text);
}

.input-group {
    margin-bottom: 18px;
}

input {
    width: 100%;
    font-size: 32px;
    padding: 16px;
    border-radius: 10px;
    border: 2px solid #e2e8f0;
    text-align: center;
    font-weight: bold;
    transition: 0.2s;
}

input:focus {
    border-color: var(--primary);
    outline: none;
    box-shadow: 0 0 0 3px rgba(31,122,31,0.15);
}

button {
    width: 100%;
    margin-top: 10px;
    font-size: 20px;
    padding: 16px;
    border: none;
    border-radius: 10px;
    background: var(--primary);
    color: white;
    font-weight: bold;
    cursor: pointer;
    transition: 0.2s;
}

button:hover {
    background: var(--primary-dark);
}

.box {
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 16px;
    font-weight: bold;
    font-size: 14px;
}

.ok {
    background: var(--ok-bg);
    border: 1px solid var(--ok-border);
}

.bad {
    background: var(--err-bg);
    border: 1px solid var(--err-border);
}

.footer {
    margin-top: 16px;
    font-size: 12px;
    color: var(--muted);
}

/* Modo móvil */
@media (max-width: 480px) {
    .card {
        padding: 26px 20px;
    }

    input {
        font-size: 36px;
        padding: 18px;
    }

    button {
        font-size: 22px;
        padding: 18px;
    }
}
</style>
</head>

<body>

<div class="container">
    <div class="card">

        <div class="logo">Sistema Control BINS</div>

        <h1>Imprimir etiqueta</h1>
        <div class="subtitle">Genera el QR para un bin en segundos</div>

        <?php if ($msg): ?>
          <div class="box ok"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <?php if ($err): ?>
          <div class="box bad"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>

        <form action="imprimir.php" method="post" autocomplete="off">

            <div class="input-group">
                <label>Número de BIN</label>
                <input 
                    name="bin" 
                    required 
                    inputmode="numeric" 
                    placeholder="Ej: 833"
                    autofocus
                >
            </div>

            <button type="submit">IMPRIMIR QR</button>

        </form>

        <div class="footer">
            Faret · Control de BINS
        </div>

    </div>
</div>

</body>
</html>
