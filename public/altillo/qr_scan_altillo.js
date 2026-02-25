document.addEventListener('DOMContentLoaded', () => {

  /* =====================================================
   * CONFIGURACIÓN BASE
   * ===================================================== */
  const btn = document.getElementById('btnScan');
  const readerDiv = document.getElementById('qr-reader');
  const resultado = document.getElementById('resultado');

  if (!btn || !readerDiv || !resultado) {
    console.error('❌ Elementos base no encontrados en el DOM');
    return;
  }

  const BASE_URL = './';

  let qrScanner = null;
  let scanning = false;
  let lastRawQR = '';

  /* =====================================================
   * BOTÓN INICIAR / DETENER ESCANEO
   * ===================================================== */
  btn.addEventListener('click', async () => {
    if (scanning) {
      await detenerEscaneo();
      return;
    }

    iniciarEscaneo();
  });

  /* =====================================================
   * INICIAR ESCANEO
   * ===================================================== */
  async function iniciarEscaneo() {
    readerDiv.style.display = 'block';
    resultado.textContent = '📷 Iniciando cámara...';

    qrScanner = new Html5Qrcode("qr-reader");

    try {
      scanning = true;
      btn.textContent = '⛔ Detener escaneo';

      await qrScanner.start(
        { facingMode: { exact: "environment" } }, // cámara trasera
        { fps: 10, qrbox: 250 },
        async (decodedText) => {
          resultado.textContent = '✅ QR leído:\n' + decodedText;

          await procesarQR(decodedText);

          await detenerEscaneo();
        },
        () => { } // errores de frame ignorados
      );

    } catch (err) {
      resultado.textContent = '❌ Error cámara:\n' + err.message;
      scanning = false;
      btn.textContent = '📷 Iniciar escaneo';
      readerDiv.style.display = 'none';
    }
  }

  /* =====================================================
   * DETENER ESCANEO
   * ===================================================== */
  async function detenerEscaneo() {
    if (qrScanner) {
      try {
        await qrScanner.stop();
      } catch { }
      qrScanner.clear();
      qrScanner = null;
    }

    scanning = false;
    btn.textContent = '📷 Iniciar escaneo';
    readerDiv.style.display = 'none';
  }

  /* =====================================================
   * FETCH SEGURO (ANTI HTML / 404 / WARNINGS PHP)
   * ===================================================== */
  async function fetchJsonSeguro(url, options = {}) {
    const res = await fetch(url, options);
    const text = await res.text();

    try {
      return JSON.parse(text);
    } catch {
      throw new Error(
        'Respuesta NO JSON desde:\n' +
        url +
        '\n\nContenido recibido:\n' +
        text
      );
    }
  }

  /* =====================================================
   * PROCESAR QR
   * ===================================================== */
  async function procesarQR(raw) {
    try {
      lastRawQR = raw;

      /* ================================
       * 1) PARSE QR
       * ================================ */
      const jParse = await fetchJsonSeguro(
        BASE_URL + 'api/parse_qr_altillo.php',
        {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ qr: raw })
        }
      );


      if (!jParse.ok) {
        resultado.textContent =
          '❌ Error parser:\n' + JSON.stringify(jParse, null, 2);
        return;
      }

      const { codigo, cantidad, lote } = jParse.data || {};


      /* ================================
       * 2) CATÁLOGO
       * ================================ */
      let descripcion = 'No encontrada';

      if (codigo) {
        const jCat = await fetchJsonSeguro(
          BASE_URL + 'api/catalogo_lookup_altillo.php?codigo=' +
          encodeURIComponent(codigo)
        );

        if (jCat.ok && jCat.found) {
          descripcion = jCat.data.descripcion;
        }
      }

      /* ================================
       * 3) MOSTRAR RESUMEN
       * ================================ */
      resultado.textContent =
        `📦 RESUMEN ALTILLO
========================
Código Producto : ${codigo ?? '—'}
Unidades Tarja  : ${cantidad ?? '—'}
Lote            : ${lote ?? '—'}

Descripción:
${descripcion}
`;

      /* ================================
       * 4) RELLENAR FORMULARIO (SI EXISTE)
       * ================================ */
      setValue('codigo_producto', codigo);
      setValue('unidades_tarja', cantidad);
      setValue('lote', lote);
      setValue('descripcion_producto', descripcion);

    } catch (err) {
      resultado.textContent = '❌ ERROR CRÍTICO:\n' + err.message;
      console.error(err);
    }
  }

  /* =====================================================
   * HELPER INPUTS
   * ===================================================== */
  function setValue(id, value) {
    const el = document.getElementById(id);
    if (el && value !== undefined && value !== null) {
      el.value = value;
    }
  }
  /* =====================================================
   * CÁLCULO SALDO → CONSUMO
   * ===================================================== */
  const saldoInput = document.getElementById('saldo_unidades');
  const consumoInput = document.getElementById('consumo_unidades');
  const tarjaInput = document.getElementById('unidades_tarja');

  if (saldoInput && consumoInput && tarjaInput) {

    saldoInput.addEventListener('input', () => {

      // Unidades tarja viene como "525,00"
      const tarja = parseFloat(
        (tarjaInput.value || '0')
          .replace(/\./g, '')
          .replace(',', '.')
      );

      const saldo = parseFloat(saldoInput.value || '0');


      if (isNaN(tarja) || isNaN(saldo)) {
        consumoInput.value = '';
        return;
      }

      const consumo = tarja - saldo;

      consumoInput.value = consumo >= 0
        ? consumo.toFixed(2)
        : '—';
    });

  }
  /* =====================================================
   * SUBMIT REGISTRO → registrar_altillo.php
   * ===================================================== */
  const form = document.getElementById('formAltillo');

  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      try {
        // Datos del operador
        const operador = document.getElementById('operador')?.value?.trim() || '';
        const np = document.getElementById('np')?.value?.trim() || '';
        // Comentario (opcional)
        const comentario = document.getElementById('comentario')?.value?.trim() || '';

        // Saldo / consumo
        const saldo = document.getElementById('saldo_unidades')?.value || '';
        const consumo = document.getElementById('consumo_unidades')?.value || '';

        // Datos QR
        const codigo = document.getElementById('codigo_producto')?.value || '';
        const descripcion = document.getElementById('descripcion_producto')?.value || '';
        const unidadesTarja = document.getElementById('unidades_tarja')?.value || '';
        const lote = document.getElementById('lote')?.value || '';

        // Validación mínima (solo lo básico por ahora)
        if (!lastRawQR) throw new Error('No hay QR leído aún.');
        if (!operador) throw new Error('Selecciona operador.');
        if (!np) throw new Error('Ingresa NP.');
        if (saldo === '') throw new Error('Ingresa saldo de unidades.');

        // Enviar al backend
        const payload = new URLSearchParams({
          operador,
          np,
          comentario,
          saldo_unidades: String(saldo),
          consumo_unidades: String(consumo),
          codigo,
          descripcion,
          unidades_tarja: String(unidadesTarja),
          lote,
          raw_qr: lastRawQR
        });

        const j = await fetchJsonSeguro(
          BASE_URL + 'api/registrar_altillo.php',
          {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: payload
          }
        );

        if (!j.ok) {
          throw new Error(j.msg || 'No se pudo registrar.');
        }

        // Feedback rápido (después lo dejamos bonito)
        const feedback = document.getElementById('feedback');

        feedback.innerHTML = `
  <div style="
    display:flex;
    align-items:center;
    gap:12px;
    background:#d1e7dd;
    color:#0f5132;
    border:1px solid #badbcc;
    padding:14px;
    border-radius:10px;
    font-weight:600;
    font-size:15px;
  ">
    <span style="font-size:22px;">✅</span>
    <div>
      <div>Registro guardado</div>
      <small style="font-weight:400;">
        Escaneo procesado correctamente
      </small>
    </div>
  </div>
`;

        // Vibración corta en móvil (si existe)
        if (navigator.vibrate) {
          navigator.vibrate(80);
        }

        // Limpia campos manuales para siguiente registro
        document.getElementById('np').value = '';
        document.getElementById('saldo_unidades').value = '';
        document.getElementById('comentario').value = '';



      } catch (err) {
        resultado.textContent = '❌ No se pudo guardar:\n' + err.message;
      }
    });
  }

});
