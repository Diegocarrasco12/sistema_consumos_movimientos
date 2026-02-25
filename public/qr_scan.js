// qr_scan.js
// Controla cámara, decodifica QR y escribe el resultado en #raw_qr.
// Usa BarcodeDetector si existe; si no, hace fallback con jsQR sobre frames del <video>.
// 🔵 Actualizado: ahora consulta /api/catalogo_lookup.php para obtener
// descripción y código desde la base local tras leer el QR.

(() => {
  const btn      = document.getElementById('btnToggleScan');
  const video    = document.getElementById('qrVideo');
  const canvas   = document.getElementById('qrCanvas');
  const ctx      = canvas.getContext('2d');
  const scanText = document.getElementById('scanText');
  const rawField = document.getElementById('raw_qr');
  const npInput  = document.getElementById('np');

  // 🔹 Contenedor donde se mostrará la info del producto
  const infoBox  = document.getElementById('producto-info');

  let stream   = null;
  let scanning = false;
  let rafId    = null;

  /**
   * Extrae el lote del texto crudo de QR (formato tipo "3821-15" o similar)
   * Usamos misma lógica simplificada que en el helper PHP parse_qr()
   */
  function extractLote(text) {
    if (!text) return null;
    const s = text.trim();
    const match = s.match(/(?:^|[^\d])0*(\d{3,6}-\d{1,3})(?:[^\d]|$)/);
    if (!match) return null;
    let lote = match[1].replace(/^0+/, '');
    const parts = lote.split('-');
    if (parts[0].startsWith('10') && parts[0].length > 4) {
      parts[0] = parts[0].substring(2);
    }
    return parts.join('-');
  }

  /**
   * Muestra información del producto en pantalla (o mensaje de error)
   */
  function mostrarInfoProducto(data) {
    if (!infoBox) return;

    if (!data.success) {
      infoBox.innerHTML = `
        <div class="alert alert-warning mt-2 p-2">
          ❌ ${data.error || 'Producto no encontrado.'}
        </div>
      `;
      return;
    }

    infoBox.innerHTML = `
      <div class="alert alert-success mt-2 p-2">
        <strong>✅ Producto encontrado</strong><br>
        <b>Código:</b> ${data.codigo}<br>
        <b>Descripción:</b> ${data.descripcion || '(sin descripción)'}<br>
        <b>Empresa:</b> ${data.empresa || '-'}<br>
        <b>UOM:</b> ${data.uom || '-'}
      </div>
    `;
  }

  /**
   * Envía el QR al backend para obtener código y descripción
   */
  async function buscarEnCatalogo(qrRaw) {
    try {
      if (infoBox) {
        infoBox.innerHTML = `
          <div class="alert alert-info mt-2 p-2">
            🔍 Buscando producto en catálogo local...
          </div>
        `;
      }

      const res = await fetch('/api/catalogo_lookup.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ qr_raw: qrRaw })
      });

      const data = await res.json();
      mostrarInfoProducto(data);

      console.log('🔎 Resultado del catálogo:', data);
    } catch (err) {
      console.error('Error al consultar catálogo:', err);
      if (infoBox) {
        infoBox.innerHTML = `
          <div class="alert alert-danger mt-2 p-2">
            ⚠️ Error al conectar con el catálogo local.
          </div>
        `;
      }
    }
  }

  function setResult(text) {
    if (!text) return;
    const val = text.trim();
    rawField.value = val;
    scanText.textContent = val;

    // feedback visual rápido
    scanText.classList.add('border', 'border-success');
    setTimeout(() => scanText.classList.remove('border', 'border-success'), 900);

    // detener escaneo y enfocar siguiente campo
    stopScan();
    npInput?.focus();

    // 🔵 Nuevo: búsqueda automática en catálogo local
    buscarEnCatalogo(val);

    // 🔵 Mantiene el evento global para otras integraciones
    const lote = extractLote(val);
    if (lote) {
      const event = new CustomEvent('sap_autofill_ready', { detail: { lote } });
      document.dispatchEvent(event);
    }
  }

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

      // Limpia texto anterior al reiniciar escaneo
      scanText.textContent = '—';
      rawField.value = '';
      if (infoBox) infoBox.innerHTML = '';

      // Primero intenta API nativa si existe
      if ('BarcodeDetector' in window) {
        try {
          const detector = new BarcodeDetector({ formats: ['qr_code'] });
          const detect = async () => {
            if (!scanning) return;
            const codes = await detector.detect(video);
            if (codes && codes.length) {
              setResult(codes[0].rawValue || codes[0].rawValueText || '');
              return;
            }
            rafId = requestAnimationFrame(detect);
          };
          detect();
          return;
        } catch (e) {
          console.warn('BarcodeDetector no disponible / falló. Usando jsQR.', e);
        }
      }

      // Fallback con jsQR
      const tick = () => {
        if (!scanning) return;
        const w = video.videoWidth, h = video.videoHeight;
        if (w && h) {
          canvas.width = w; canvas.height = h;
          ctx.drawImage(video, 0, 0, w, h);
          const img = ctx.getImageData(0, 0, w, h);
          const code = window.jsQR ? jsQR(img.data, w, h, { inversionAttempts: 'dontInvert' }) : null;
          if (code && code.data) {
            setResult(code.data);
            return;
          }
        }
        rafId = requestAnimationFrame(tick);
      };
      tick();
    } catch (err) {
      console.error(err);
      alert('No se pudo acceder a la cámara. Revisa permisos del navegador.');
    }
  }

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

  btn?.addEventListener('click', () => {
    scanning ? stopScan() : startScan();
  });

  // Limpieza al salir/navegar (por si el usuario cambia de ruta con la cámara abierta)
  window.addEventListener('beforeunload', stopScan);
})();
