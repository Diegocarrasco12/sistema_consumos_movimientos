# Integración con SAP Business One

El sistema se integra con SAP mediante consultas directas a la base de datos SQL Server.

La integración es de solo lectura.

## Objetivo

Obtener información confiable de:

- productos
- lotes
- cantidades disponibles

## Tablas utilizadas

### OIBT

Tabla de lotes.

Contiene:

- ItemCode
- BatchNum
- Quantity
- WhsCode

### OITM

Tabla maestra de productos.

Contiene:

- ItemCode
- ItemName
- InvntryUom

## Consulta utilizada

SELECT
    T0.ItemCode,
    T1.ItemName,
    T0.BatchNum,
    T0.Quantity
FROM OIBT T0
INNER JOIN OITM T1
    ON T0.ItemCode = T1.ItemCode
WHERE T0.BatchNum = ?