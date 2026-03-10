# Arquitectura del Sistema

La plataforma de control logístico está compuesta por varios subsistemas diseñados para registrar eventos operacionales dentro de la planta.

## Componentes principales

### Backend

PHP

Responsable de:

- lógica de negocio
- consultas a base de datos
- integración con SAP
- generación de reportes

---

### Frontend

HTML  
Bootstrap  
JavaScript

Utilizado para interfaces operacionales simples adaptadas para uso en planta.

---

### Lectura de códigos QR

Biblioteca utilizada:

jsQR

Permite utilizar la cámara del dispositivo para capturar códigos QR directamente desde el navegador.

---

### Base de datos local

Motor:

MySQL

Contiene los registros operacionales generados por los distintos módulos del sistema.

---

### Integración con SAP Business One

La plataforma consulta directamente la base de datos de SAP mediante conexión SQL Server utilizando PDO.

La integración es de solo lectura para evitar modificaciones accidentales en SAP.
