# Subdominio de pruebas en Hostinger

Objetivo: tener `https://pruebas.TUDOMINIO.com` con el desarrollo real (compras con pasarela simulada, panel, correo) para que Norman y la clienta lo vean sin tocar el sitio actual. Lo que no pude verificar desde mi entorno está marcado con ⚠ y se confirma en hPanel.

## 1. Crear el subdominio (lo haces tú en hPanel)
1. hPanel → **Dominios** → tu dominio → **Subdominios** → crear `pruebas`.
2. Fíjate en la **carpeta** que asigna (suele ser `public_html/pruebas`) ⚠. Si te deja elegirla, mejor una carpeta propia fuera de `public_html` (ver paso 3).
3. **SSL**: activa el certificado gratuito para el subdominio (el sitio redirige a HTTPS).
4. **PHP**: Avanzado → Configuración de PHP → versión **8.1 o superior** (probado en 8.4). Extensiones: `pdo_mysql`, `mbstring`, `intl` no hace falta.

## 2. Base de datos y correo
- hPanel → **Bases de datos MySQL** → crear base, usuario y clave. Anota los tres.
- hPanel → **Correos** → crear un buzón `tickets@TUDOMINIO.com` ⚠ (la pantalla muestra servidor y puerto SMTP: normalmente `smtp.hostinger.com`, 465 con SSL o 587 con TLS).
- Si el dominio tiene otros proveedores de correo (Google Workspace), avísame: cambia dónde crear el buzón y los registros SPF/DKIM.

## 3. Subir el código
Estructura más simple (zip "plano", `tools/build-zip.py SALIDA.zip --plano`): todo va en `public_html/pruebas`; las carpetas internas se protegen con su `.htaccess`. La estructura con `pruebas-app` fuera de `public_html` sigue soportada y es la más segura para producción:
```
/home/uXXXX/pruebas-app/        ← app/, config/, database/, storage/, tools/
/home/uXXXX/pruebas-app/public/ ← carpeta pública del subdominio
```
- Si hPanel deja apuntar el subdominio a `pruebas-app/public` ⚠, listo.
- Si el subdominio queda fijo en `public_html/pruebas`: sube el contenido de `public/` ahí y el resto a una carpeta hermana; avísame y ajusto la ruta en `public/index.php` (una línea).

Cómo subir: **Administrador de archivos** (arrastrar un .zip del repo) o **Git** de hPanel apuntando al repositorio `easymktok-ops/repo`, rama `claude/wonderful-allen-qhmfe5` ⚠. No subas `assets-src/`, `tests/`, `docs/` ni los .jpg sueltos de la raíz.

## 4. Configuración
1. Copia `config/config.staging.example.php` a `config/config.php` y completa dominio, base de datos, buzón y clave de admin.
2. Hash de la clave del panel: en el **Terminal/SSH** de hPanel ⚠ (o en tu computador con PHP): `php -r "echo password_hash('clave-larga', PASSWORD_DEFAULT);"`.
3. Crear las tablas, una vez: con SSH `php tools/migrate.php`; sin SSH, abrir `https://pruebas.TUDOMINIO.com/instalar.php?token=TU_TOKEN` (el token es `install_token` de config.php) y después BORRAR `public/instalar.php`.
4. Permisos de escritura en `storage/` (755 o 775).

## 5. Protección del entorno de pruebas
- El sitio ya sale con `noindex` y `X-Robots-Tag` mientras `env` no sea `production`. La pasarela simulada solo funciona fuera de producción.
- Además, ponle clave de acceso al subdominio: hPanel → **Avanzado → Protección con contraseña de directorios** sobre la carpeta pública ⚠. Así nadie ajeno ve ni prueba compras falsas.
- Cuando se pase a producción: `env` => `production`, `provider` => `mercadopago`, dominio real, y se quita la protección.

## 6. Qué probar al subirlo
1. Home y landings `/medellin/`, `/bogota/`, `/cartagena/`.
2. Compra completa en `/comprar/` y reserva en `/reservar/cartagena/` con el botón "Pago aprobado" de la pasarela simulada.
3. Que llegue el correo con ticket o voucher (si no llega: revisar carpeta de spam y el log del servidor).
4. Panel `/admin/`: pedidos, descargas CSV, precios.
5. Esta es también la primera prueba de **MySQL** (hasta hoy solo probé SQLite): si algo falla en la migración, es lo primero que corregimos.

## 7. Mercado Pago más adelante
El subdominio ya trae HTTPS, que es lo que Mercado Pago exige para las notificaciones: la URL será `https://pruebas.TUDOMINIO.com/webhook/mercadopago`. Falta la integración real (necesita credenciales de prueba y la documentación oficial).
