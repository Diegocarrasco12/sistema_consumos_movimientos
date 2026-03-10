// qr_scan.js
// Escaneo QR + consulta SAP + autocompletado formulario
// VERSION ESTABLE — autocompleta código, descripción y TARJA KG

(() => {

  const btn = document.getElementById('btnToggleScan');
  const video = document.getElementById('qrVideo');
  const canvas = document.getElementById('qrCanvas');
  const ctx = canvas.getContext('2d');

  const scanText = document.getElementById('scanText');
  const rawField = document.getElementById('raw_qr');
  const npInput = document.getElementById('np');

  const infoBox = document.getElementById('producto-info');

  const codigoInput = document.getElementById('codigo');
  const descripcionInput = document.getElementById('descripcion');

  const tarjaHidden = document.getElementById('tarja_kg');
  const tarjaVisible = document.getElementById('tarja_visible');

  let stream = null;
  let scanning = false;
  let rafId = null;

  // ===============================
  // EXTRAER PESO DESDE QR
  // ===============================
  function extraerPesoQR(qr) {

    if (!qr) return null;
  
    const s = qr.replace(/\s+/g, '').trim();
  
    // Buscar lote codificado al final
    const loteMatch = s.match(/0+1?0*\d{2,6}-\d{1,4}(?=#|$)/);
    if (!loteMatch) return null;
  
    const posLote = s.indexOf(loteMatch[0]);
    const antesDelLote = s.substring(0, posLote);
  
    if (!antesDelLote) return null;
  
    // Tomar el ÚLTIMO bloque 37 antes del lote
    const posUltimo37 = antesDelLote.lastIndexOf('37');
  
    if (posUltimo37 === -1) return null;
  
    const rawPeso = antesDelLote.substring(posUltimo37 + 2);
  
    if (!rawPeso) return null;
  
    // Caso con coma: 371188, / 37850, / 371000,
    if (rawPeso.includes(',')) {
      const parteEntera = rawPeso.split(',')[0];
      const digits = parteEntera.replace(/\D/g, '');
  
      if (!digits) return null;
  
      const limpio = digits.replace(/^0+/, '');
  
      return limpio === '' ? 0 : parseFloat(limpio);
    }
  
    // Caso sin coma: 37000002000 / 3700012000 / 3700000500
    const digits = rawPeso.replace(/\D/g, '');
  
    if (!digits) return null;
  
    const valor = parseFloat(digits) / 100;
  
    if (Number.isInteger(valor)) {
      return valor;
    }
  
    return valor;
  }

  // ===============================
  // MOSTRAR INFO PRODUCTO
  // ===============================

  function mostrarInfoProducto(data) {

    if (!infoBox) return;

    if (!data.ok) {

      infoBox.innerHTML = `
        <div class="alert alert-warning mt-2 p-2">
          ❌ ${data.error || 'Producto no encontrado'}
        </div>
      `;

      return;
    }

    infoBox.innerHTML = `
      <div class="alert alert-success mt-2 p-2">
        <strong>✅ Producto encontrado</strong><br>
        <b>Código:</b> ${data.item_code}<br>
        <b>Descripción:</b> ${data.item_name}<br>
        <b>Fuente:</b> ${data.source || 'SAP'}
      </div>
    `;
  }

  // ===============================
  // AUTOCOMPLETAR FORMULARIO
  // ===============================

  function llenarFormulario(data) {

    if (!data.ok) return;

    // Código
    if (codigoInput) {
      codigoInput.value = data.item_code || '';
    }

    // Descripción
    if (descripcionInput) {
      descripcionInput.value = data.item_name || '';
    }


  }

  // ===============================
  // CONSULTAR SAP
  // ===============================

  async function consultarSAP(qrRaw) {

    if (!qrRaw) return;

    try {

      if (infoBox) {

        infoBox.innerHTML = `
          <div class="alert alert-info mt-2 p-2">
            🔍 Buscando producto en SAP...
          </div>
        `;

      }

      const res = await fetch('/api/producto_por_lote.php?qr=' + encodeURIComponent(qrRaw));

      const data = await res.json();

      console.log("SAP RESPONSE:", data);

      mostrarInfoProducto(data);

      llenarFormulario(data);

    }
    catch (err) {

      console.error("Error SAP:", err);

      if (infoBox) {

        infoBox.innerHTML = `
          <div class="alert alert-danger mt-2 p-2">
            ⚠️ Error consultando SAP
          </div>
        `;

      }

    }

  }

  // ===============================
  // RESULTADO QR
  // ===============================

  function setResult(text) {

    if (!text) return;

    const val = text.trim();

    rawField.value = val;

    scanText.textContent = val;

    scanText.classList.add('border', 'border-success');

    setTimeout(() => {
      scanText.classList.remove('border', 'border-success');
    }, 800);

    stopScan();

    npInput?.focus();

    // limpiar peso anterior
    if (tarjaHidden) tarjaHidden.value = '';
    if (tarjaVisible) tarjaVisible.value = '';

    // EXTRAER PESO DESDE QR
    const pesoQR = extraerPesoQR(val);

    if (pesoQR !== null) {

      if (tarjaHidden) {
        tarjaHidden.value = pesoQR;
      }

      if (tarjaVisible) {
        tarjaVisible.value = pesoQR;
      }

    }

    consultarSAP(val);

  }

  // ===============================
  // INICIAR ESCANEO
  // ===============================

  async function startScan() {

    try {

      stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: { ideal: 'environment' } },
        audio: false
      });

      video.srcObject = stream;

      await video.play();

      video.classList.remove('d-none');

      scanning = true;

      btn.textContent = '⏹️ Detener escaneo';

      scanText.textContent = '—';

      rawField.value = '';

      if (infoBox) infoBox.innerHTML = '';

      const tick = () => {

        if (!scanning) return;

        const w = video.videoWidth;
        const h = video.videoHeight;

        if (w && h) {

          canvas.width = w;
          canvas.height = h;

          ctx.drawImage(video, 0, 0, w, h);

          const img = ctx.getImageData(0, 0, w, h);

          const code = jsQR(img.data, w, h);

          if (code && code.data) {

            setResult(code.data);

            return;

          }

        }

        rafId = requestAnimationFrame(tick);

      };

      tick();

    }
    catch (err) {

      console.error(err);

      alert("No se pudo acceder a la cámara");

    }

  }

  // ===============================
  // DETENER ESCANEO
  // ===============================

  function stopScan() {

    scanning = false;

    btn.textContent = '▶️ Iniciar escaneo';

    video.classList.add('d-none');

    if (rafId) cancelAnimationFrame(rafId);

    if (stream) {

      stream.getTracks().forEach(t => t.stop());

      stream = null;

    }

  }

  // ===============================
  // BOTÓN ESCANEO
  // ===============================

  btn?.addEventListener('click', () => {

    scanning ? stopScan() : startScan();

  });

  window.addEventListener('beforeunload', stopScan);

})();