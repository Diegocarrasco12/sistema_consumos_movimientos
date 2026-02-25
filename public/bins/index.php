<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>BINS · Control</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Estilos base -->
    <link rel="stylesheet" href="../styles.css">

    <style>
        .scan-box,
        .result-box,
        .form-box {
            background: #fff;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, .08);
        }

        .scan-box button {
            width: 100%;
            background: #0d6efd;
            color: #fff;
            border: none;
            padding: 18px 20px;
            border-radius: 12px;
            font-size: 20px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 10px rgba(13, 110, 253, .35);
        }

        .scan-box button:active {
            transform: scale(.97);
        }

        #qr-reader {
            margin: 15px auto 0;
            border-radius: 10px;
            overflow: hidden;
        }

        .form-group {
            margin-bottom: 12px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 9px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 15px;
            box-sizing: border-box;
            height: 38px;
        }

        .btn-primary {
            width: 100%;
            background: #198754;
            color: #fff;
            border: none;
            padding: 12px;
            font-size: 17px;
            border-radius: 8px;
            cursor: pointer;
        }

        .btn-primary:active {
            transform: scale(.98);
        }

        .bin-big {
            font-size: 34px;
            font-weight: 800;
            text-align: center;
            padding: 10px 0;
        }

        .muted {
            color: #666;
            font-size: 13px;
        }

        .ok {
            background: #d1e7dd;
            color: #0f5132;
            padding: 10px;
            border-radius: 8px;
        }

        .err {
            background: #f8d7da;
            color: #842029;
            padding: 10px;
            border-radius: 8px;
        }
    </style>

    <!-- QR -->
    <script src="https://unpkg.com/html5-qrcode"></script>
</head>

<body>
    <main class="container">
        <h1>📦 Control BINS</h1>

        <!-- ESCANEO -->
        <section class="scan-box">
            <label>Escaneo QR</label>
            <button id="btnScan" type="button">📷 Iniciar escaneo</button>
            <div id="qr-reader" style="display:none;width:100%;"></div>
            <small class="muted" style="display:block;margin-top:8px;">
                Use la cámara del teléfono para escanear la etiqueta
            </small>
        </section>

        <!-- RESULTADO -->
        <section class="result-box">
            <h3>BIN identificado</h3>
            <div id="binBig" class="bin-big">—</div>
            <pre id="resultado"
                style="background:#f8f9fa;padding:10px;border-radius:6px;font-size:13px;overflow-x:auto;">
Esperando escaneo...
        </pre>
        </section>

        <!-- FEEDBACK -->
        <section class="result-box">
            <div id="feedback"></div>
        </section>

        <!-- FORM -->
        <section class="form-box">
            <h3>Registrar movimiento</h3>

            <!-- ❗ SIN action NI method → todo se maneja por JS -->
            <form id="formBins" enctype="multipart/form-data" novalidate>

                <input type="hidden" id="bin_codigo" name="bin_codigo" required>

                <div class="form-group">
                    <label for="tipo">Tipo</label>
                    <select id="tipo" name="tipo" required>
                        <option value="">Seleccione...</option>
                        <option value="ENTRADA">ENTRADA</option>
                        <option value="SALIDA">SALIDA</option>
                        <option value="LAVADO">LAVADO DE BINS</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="documento">N° Documento</label>
                    <input type="text" id="documento" name="documento" required>
                </div>
                <div class="form-group">
                    <label for="proveedor">Proveedor</label>
                    <select id="proveedor" name="proveedor" required>
                        <option value="">Seleccione proveedor...</option>
                        <option value="INNPACK">INNPACK</option>
                        <option value="SUPERFRUIT">SUPERFRUIT</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="estado_bin">Estado del BIN</label>
                    <input
                        type="text"
                        id="estado_bin"
                        name="estado_bin"
                        placeholder="Ej: sucio, dañado, sin tapa, OK, etc.">
                </div>

                <div class="form-group">
                    <label for="archivo">Foto documento (opcional)</label>
                    <input type="file"
                        id="archivo"
                        name="archivo"
                        accept="image/*"
                        capture="environment">
                    <small class="muted">Se puede abrir la cámara del teléfono.</small>
                </div>

                <button type="submit" class="btn-primary">💾 Guardar movimiento</button>

                <div style="text-align:center;margin-top:10px;">
                    <a href="list_bins.php" class="muted">📋 Ver movimientos</a>
                </div>
            </form>
        </section>
    </main>

    <!-- JS con cache-buster REAL -->
    <script src="qr_scan_bins.js?v=20260109"></script>
</body>

</html>