# Sistema de Control Logístico – Faret / Innpack

Plataforma interna desarrollada para automatizar procesos operacionales de planta mediante el uso de códigos QR, integración con ERP y registro centralizado de eventos logísticos.

El sistema permite registrar movimientos de materiales, control de contenedores reutilizables (bins), consumo de papel de producción, almacenamiento en altillo y seguimiento de pallets.

## Objetivo

Digitalizar procesos operacionales que anteriormente se realizaban manualmente, mejorando:

- trazabilidad
- control de inventario
- registro de movimientos
- integración con sistemas de ERP
- eficiencia operativa en planta

## Arquitectura tecnológica

Backend  
PHP

Frontend  
HTML + Bootstrap + JavaScript

Lectura QR  
jsQR

Base de datos local  
MySQL

ERP  


SQL Server

Integración ERP  


## Subsistemas incluidos

El sistema está compuesto por múltiples módulos operacionales:

### Consumo de Papel
Registro de consumo de tarjas mediante escaneo QR con integración a ERP

### Bins
Control de movimiento de contenedores reutilizables dentro de la planta.

### Lavado de Bins
Registro de ciclos de lavado y control sanitario de bins.

### Altillo
Gestión de cajas almacenadas en altillo mediante QR.

### Pallets
Control de movimientos de pallets asociados a producción.

### Generación de QR
Generación e impresión de etiquetas QR para bins y materiales.


## Estado del sistema

Sistema operativo en entorno productivo.

Módulos activos:

- Consumo de papel
- Movimiento de bins
- Lavado de bins
- Altillo
- Pallets
- Generación de QR