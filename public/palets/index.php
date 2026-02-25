<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>PALETS · Control</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Estilos base -->
    <link rel="stylesheet" href="../styles.css">

    <!-- Bootstrap solo para look profesional -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            min-height: 100vh;
        }

        .app-container {
            max-width: 520px;
            margin: auto;
            padding: 20px;
        }

        .app-card {
            background: #fff;
            border-radius: 14px;
            padding: 18px;
            margin-bottom: 18px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, .15);
        }

        .app-title {
            color: #fff;
            text-align: center;
            font-weight: 800;
            margin-bottom: 20px;
        }

        .scan-btn {
            width: 100%;
            background: linear-gradient(135deg, #198754, #157347);
            color: #fff;
            border: none;
            padding: 20px;
            border-radius: 14px;
            font-size: 22px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 6px 16px rgba(25, 135, 84, .4);
            transition: .2s;
        }

        .scan-btn:active {
            transform: scale(.97);
        }

        .palet-big {
            font-size: 36px;
            font-weight: 900;
            text-align: center;
            padding: 10px 0;
            color: #0d6efd;
        }

        .result-raw {
            background: #f1f3f5;
            border-radius: 8px;
            padding: 10px;
            font-size: 13px;
            word-break: break-all;
        }

        .btn-save {
            width: 100%;
            background: #0d6efd;
            color: #fff;
            border: none;
            padding: 14px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 10px;
        }

        .btn-save:active {
            transform: scale(.97);
        }

        .link-historial {
            display: block;
            text-align: center;
            margin-top: 12px;
            font-weight: 600;
            text-decoration: none;
        }

        .ok-alert {
            background: #d1e7dd;
            color: #0f5132;
            padding: 12px;
            border-radius: 10px;
            text-align: center;
            font-weight: 600;
            margin-bottom: 15px;
        }
    </style>

    <!-- Librería QR -->
    <script src="https://unpkg.com/html5-qrcode"></script>
</head>

<body>

<div class="app-container">

    <h1 class="app-title">📦 CONTROL DE PALETS</h1>

    <?php if (isset($_GET['ok'])): ?>
        <div class="ok-alert">
            ✔ Movimiento registrado correctamente
        </div>
    <?php endif; ?>

    <!-- ESCANEO -->
    <div class="app-card">
        <button id="btnScan" class="scan-btn">
            📷 INICIAR ESCANEO
        </button>
        <div id="qr-reader" style="display:none;width:100%;margin-top:15px;"></div>
    </div>

    <!-- RESULTADO -->
    <div class="app-card text-center">
        <div class="text-muted small">Palet detectado</div>
        <div id="paletBig" class="palet-big">—</div>

        <div id="resultado" class="result-raw">
            Esperando escaneo...
        </div>
    </div>

    <!-- FORMULARIO -->
    <div class="app-card">
        <h5 class="mb-3 text-center">Registrar movimiento</h5>

        <form action="guardar.php" method="post">
            <input type="hidden" id="qr" name="qr" required>

            <div class="mb-3">
                <label class="form-label">Tipo de movimiento</label>
                <select name="tipo" class="form-select" required>
                    <option value="">Seleccione...</option>
                    <option value="ENTRADA">ENTRADA</option>
                    <option value="SALIDA">SALIDA</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Planta</label>
                <select name="planta" class="form-select" required>
                    <option value="">Seleccione planta...</option>
                    <option value="INNPACK">INNPACK</option>
                    <option value="FARET">FARET</option>
                    <option value="SFM">SFM</option>
                    <option value="EUROPA">EUROPA</option>
                    <option value="DOMINGO ARTEAGA">DOMINGO ARTEAGA</option>
                </select>
            </div>

            <button type="submit" class="btn-save">
                💾 Guardar movimiento
            </button>

            <a href="historial.php" class="link-historial">
                📋 Ver historial
            </a>
        </form>
    </div>

</div>

<script>
    const btn = document.getElementById('btnScan');
    const readerDiv = document.getElementById('qr-reader');
    const qrInput = document.getElementById('qr');
    const resultado = document.getElementById('resultado');
    const paletBig = document.getElementById('paletBig');

    let qr;

    btn.addEventListener('click', () => {
        readerDiv.style.display = 'block';

        qr = new Html5Qrcode("qr-reader");
        qr.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: 250 },
            qrText => {
                qr.stop();

                qrInput.value = qrText;
                resultado.textContent = qrText;

                let palet = qrText.split('#')[0];
                paletBig.textContent = palet;
            }
        );
    });
</script>

</body>
</html>
