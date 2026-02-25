// ======================================================
// QR SCAN BINS
// ======================================================

let qrScanner = null;
let scanning = false;
let submitting = false; // ⛔ evita doble submit

const btnScan = document.getElementById('btnScan');
const qrReader = document.getElementById('qr-reader');
const resultado = document.getElementById('resultado');
const binBig = document.getElementById('binBig');
const inputBin = document.getElementById('bin_codigo'); // se mantiene por compatibilidad
const feedbackBox = document.getElementById('feedback');
const formBins = document.getElementById('formBins');

// ======================================================
// BUFFER DE BINS ESCANEADOS (PRE-GUARDADO)
// ======================================================
const binsEscaneados = [];

// ======================================================
// INICIAR ESCANEO
// ======================================================
btnScan.addEventListener('click', async () => {
  if (scanning) return;

  feedbackBox.innerHTML = '';
  resultado.textContent = 'Escaneando...';
  qrReader.style.display = 'block';
  scanning = true;

  try {
    qrScanner = new Html5Qrcode("qr-reader");

    await qrScanner.start(
      { facingMode: "environment" },
      { fps: 10, qrbox: 250 },
      onScanSuccess,
      () => { } // silencioso
    );
  } catch (e) {
    scanning = false;
    feedbackBox.innerHTML = `<div class="err">No se pudo iniciar la cámara</div>`;
    console.error(e);
  }
});

// ======================================================
// QR OK
// ======================================================
function onScanSuccess(decodedText) {
  try {
    let binCodigo = '';

    if (decodedText.includes('?')) {
      const url = new URL(decodedText);
      binCodigo = url.searchParams.get('bin') || '';
    } else {
      binCodigo = decodedText.trim();
    }

    if (!binCodigo) throw new Error('BIN no encontrado en QR');

    // ============================================
    // Normalización flexible de BIN
    // Acepta:
    // BIN-CALLE1-820 → Calle 1-820
    // BIN-820        → BIN-820
    // ============================================

    // Caso antiguo: BIN-CALLE1-820
    let m = binCodigo.match(/^BIN-CALLE(\d+)-(\d+)$/i);
    if (m) {
      binCodigo = `Calle ${m[1]}-${m[2]}`;
    } else {

      // Caso nuevo: BIN-820
      let simple = binCodigo.match(/^BIN-(\d+)$/i);
      if (simple) {
        binCodigo = `BIN-${simple[1]}`;
      }
    }


    // Evitar duplicados
    if (binsEscaneados.includes(binCodigo)) {
      feedbackBox.innerHTML = `<div class="err">BIN ya escaneado</div>`;
      detenerScanner();
      return;
    }

    // Guardar BIN en memoria
    binsEscaneados.push(binCodigo);

    // Feedback visual
    binBig.textContent = binCodigo;
    resultado.textContent = `BIN escaneados: ${binsEscaneados.length}`;
    feedbackBox.innerHTML = `<div class="ok">BIN agregado (${binsEscaneados.length})</div>`;

    detenerScanner();

  } catch (e) {
    feedbackBox.innerHTML = `<div class="err">QR inválido para BINS</div>`;
    console.error(e);
  }
}

// ======================================================
// DETENER CÁMARA
// ======================================================
async function detenerScanner() {
  if (!qrScanner) return;

  try {
    await qrScanner.stop();
    await qrScanner.clear();
  } catch (e) {
    console.warn('Scanner ya detenido');
  } finally {
    qrReader.style.display = 'none';
    scanning = false;
    qrScanner = null;
  }
}

// ======================================================
// COMPRESIÓN DE IMAGEN (SEGURO)
// ======================================================
function compressImage(file) {
  return new Promise((resolve) => {
    const img = new Image();
    const reader = new FileReader();

    reader.onload = e => img.src = e.target.result;
    reader.readAsDataURL(file);

    img.onload = () => {
      const canvas = document.createElement('canvas');
      const MAX_WIDTH = 1024;
      const scale = Math.min(1, MAX_WIDTH / img.width);

      canvas.width = img.width * scale;
      canvas.height = img.height * scale;

      const ctx = canvas.getContext('2d');
      ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

      canvas.toBlob(blob => {
        if (!blob) {
          resolve(file);
          return;
        }
        resolve(new File([blob], 'img.jpg', { type: 'image/jpeg' }));
      }, 'image/jpeg', 0.7);
    };

    img.onerror = () => resolve(file);
  });
}

// ======================================================
// SUBMIT FORM (ENVÍA TODOS LOS BIN ESCANEADOS)
// ======================================================
formBins.addEventListener('submit', async (e) => {
  e.preventDefault();
  if (submitting) return;

  submitting = true;
  feedbackBox.innerHTML = '';

  const tipo = document.getElementById('tipo').value;

  if (
    !tipo ||
    !document.getElementById('documento').value ||
    !document.getElementById('proveedor').value ||
    (tipo !== 'LAVADO' && binsEscaneados.length === 0)
  ) {

    feedbackBox.innerHTML = `<div class="err">
    Debe escanear al menos un BIN (excepto en LAVADO) y completar los datos
  </div>`;
    submitting = false;
    return;
  }


  // ==================================================
  // FORM DATA
  // ==================================================
  const formData = new FormData(formBins);

  // 🔥 ENVIAR TODOS LOS BIN ESCANEADOS
  let binsParaEnviar = binsEscaneados;

  // 👉 Caso especial: LAVADO con 1 BIN
  if (tipo === 'LAVADO' && binsEscaneados.length === 0) {
    const binUnico = binBig.textContent.trim();
    binsParaEnviar = binUnico && binUnico !== '—' ? [binUnico] : [];
  }

  formData.append('bins', JSON.stringify(binsParaEnviar));


  try {
    // 📸 Imagen opcional (se mantiene igual que antes)
    const fileInput = document.getElementById('archivo');
    if (fileInput && fileInput.files.length > 0) {
      const compressed = await compressImage(fileInput.files[0]);
      formData.set('archivo', compressed);
    }

    // ==================================================
    // FETCH (EL BACKEND AÚN NO ESTÁ LISTO, SIGUIENTE PASO)
    // ==================================================
    const res = await fetch('/bins/api/guardar_movimiento.php', {
      method: 'POST',
      body: formData
    });

    const text = await res.text();

    let data;
    try {
      data = JSON.parse(text);
    } catch {
      feedbackBox.innerHTML = `
        <div class="err">
          <strong>Respuesta cruda del servidor:</strong>
          <pre style="white-space:pre-wrap;font-size:12px">${text}</pre>
        </div>
      `;
      submitting = false;
      return;
    }

    if (!res.ok || !data.ok) {
      throw new Error(data.msg || 'Error al guardar movimientos');
    }

    // ==================================================
    // OK (LIMPIAR TODO)
    // ==================================================
    feedbackBox.innerHTML = `<div class="ok">
      ${binsEscaneados.length} BIN guardados correctamente
    </div>`;

    binsEscaneados.length = 0; // 🔥 vaciar buffer
    formBins.reset();
    binBig.textContent = '—';
    resultado.textContent = 'Esperando escaneo...';

  } catch (err) {
    console.error(err);
    feedbackBox.innerHTML = `<div class="err">${err.message}</div>`;
  } finally {
    submitting = false;
  }
});

