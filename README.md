# Sistema de Control Logístico

Plataforma interna desarrollada para automatizar procesos operacionales de planta mediante el uso de códigos QR, integración con ERP y registro centralizado de eventos logísticos.

El sistema permite registrar movimientos de materiales, control de contenedores reutilizables (bins), consumo de papel de producción, almacenamiento en altillo y seguimiento de pallets.

---

# Objetivo

Digitalizar procesos operacionales que anteriormente se realizaban manualmente, mejorando:

- trazabilidad
- control de inventario
- registro de movimientos
- integración con sistemas ERP
- eficiencia operativa en planta

---

# Arquitectura Tecnológica

**Backend**  
PHP

**Frontend**  
HTML + Bootstrap + JavaScript

**Lectura de QR**  
jsQR

**Base de datos local**  
MySQL

**Integración ERP**  
Conexión de solo lectura a base de datos ERP mediante SQL Server.

---

# Subsistemas incluidos

El sistema está compuesto por múltiples módulos operacionales:

### Consumo de Papel
Registro de consumo de tarjas mediante escaneo QR con integración al catálogo de productos del ERP.

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

---

# Estado del sistema

Sistema operativo en entorno productivo.

Módulos activos:

- Consumo de papel
- Movimiento de bins
- Lavado de bins
- Altillo
- Pallets
- Generación de QR

---

# Uso

Este repositorio contiene únicamente el código base del sistema.  
La documentación técnica interna, configuración de infraestructura y detalles operacionales no se incluyen en este repositorio por razones de seguridad.