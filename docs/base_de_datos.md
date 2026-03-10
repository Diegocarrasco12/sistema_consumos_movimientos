# Modelo de Datos

La base de datos local almacena registros operacionales generados por los distintos módulos del sistema.

## Tabla: tarjas_scan

Registra consumo de papel.

Campos:

id  
descripcion  
codigo  
consumo_kg  
np  
tarja_kg  
saldo_kg  
lote  
estado  
salida  
raw_qr  
id_usuario  
fecha

---

## Tabla: bins

Registra identificadores de bins.

---

## Tabla: bins_movimientos

Registra movimientos de bins dentro de la planta.

---

## Tabla: bins_lavado

Registra ciclos de lavado de bins.

---

## Tabla: altillo

Registra almacenamiento de cajas en altillo.

---

## Tabla: pallets

Registra movimientos de pallets.