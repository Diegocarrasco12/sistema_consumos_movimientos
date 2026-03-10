# Flujos Operacionales del Sistema

El sistema registra distintos eventos operacionales mediante escaneo de códigos QR.

## 1. Consumo de papel

Flujo:

QR escaneado  
↓  
parse_qr()  
↓  
extracción del lote  
↓  
consulta SAP  
↓  
obtención de producto y peso  
↓  
cálculo de consumo  
↓  
registro en base de datos

---

## 2. Movimiento de Bins

Permite registrar movimientos de contenedores reutilizables dentro de la planta.

Eventos registrados:

- ingreso
- traslado
- lavado
- salida

Cada movimiento queda asociado a:

- identificador del bin
- ubicación
- operador
- fecha

---

## 3. Lavado de Bins

Registro de procesos de lavado de bins utilizados en producción.

Información registrada:

- bin lavado
- fecha
- operador
- estado sanitario

---

## 4. Altillo

Control de almacenamiento en altillo.

Permite registrar:

- ingreso de cajas
- ubicación en altillo
- retiro de cajas

---

## 5. Pallets

Registro de movimientos de pallets asociados a producción o despacho.

Permite mantener trazabilidad de:

- origen
- destino
- lote
- fecha de movimiento

---

## 6. Generación de QR

El sistema permite generar etiquetas QR para:

- bins
- materiales
- pallets