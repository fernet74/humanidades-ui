# humanidades-ui

Interfaz institucional compartida para los sistemas internos del **Departamento
de Servicios Informáticos** de la **Facultad de Humanidades – UNMdP**.

Una sola fuente de verdad para estilos, fuentes, logos y la **consola de
configuración de tema**. Se monta como submódulo `ui/` en cada sistema
(Inventario de Red, Bajas Patrimoniales, Panel de Mantenimiento LXC).

```
humanidades.css      Hoja única, basada en el Manual de Imagen FH.
                     Tokens CSS: primitivas → semánticas → componentes.
fonts/               Barlow + Montserrat + Inter self-host (.woff2, OFL).
                     fonts.css con @font-face de rutas relativas.
logos/               SVG/PNG institucionales (FH y UNMdP).
theme/
  tema.php                    Módulo PHP: lee la tabla `configuracion` y emite
                              los tokens + <link>s en el <head>.
  configuracion.php           Vista de la consola (form + preview en vivo).
  configuracion_procesar.php  Guardado (colores, fuentes, logos, densidad…).
  sql/configuracion.sql       CREATE TABLE clave/valor (idempotente).
```

## Integrar en un sistema

Suponiendo que la app se sirve desde su raíz (`index.php`, `layout.php`, …):

### 1. Agregar el submódulo

```bash
git submodule add https://github.com/fernet74/humanidades-ui ui
git commit -m "Agrega humanidades-ui como submódulo"
```

En **Inventario de Red** la app vive en una subcarpeta; el submódulo va ahí
(`.../v0.5.1.1/ui`) y `TEMA_UI_BASE` sigue siendo `'ui'` porque es relativo a
la página.

### 2. Crear la tabla

```bash
mysql -u USUARIO -p NOMBRE_BASE < ui/theme/sql/configuracion.sql
```

### 3. Cargar el módulo en el bootstrap

En `functions.php` (o al inicio de `layout.php`), **antes** de `layout_header()`:

```php
define('TEMA_UI_BASE', 'ui');                       // opcional (default 'ui')
define('TEMA_NOMBRE_DEFECTO', 'Inventario de Red'); // nombre de fábrica del sistema
require_once __DIR__ . '/ui/theme/tema.php';
```

### 4. `layout.php` — `<head>`

Reemplazar los `<link>` de Font Awesome (CDN), Google Fonts y `style.css` por:

```php
<!-- ...si el sistema usa Bootstrap, su <link> va ANTES de esta línea... -->
<?php tema_head_tags(); ?>
```

`tema_head_tags()` emite: `fonts/fonts.css`, `humanidades.css`, Font Awesome
(CDN), el favicon configurado y un `<style>` con los tokens del tema.
Borrar el `style.css` local del repo.

### 5. `layout.php` — logos y textos

```php
<img src="<?= htmlspecialchars(tema_logo_url('logo_header')) ?>" class="logo-img" alt="">
...
<img src="<?= htmlspecialchars(tema_logo_url('logo_footer')) ?>" class="footer-logo" alt="">
<p><i class="fas fa-envelope"></i> <?= htmlspecialchars(tema('footer_email')) ?></p>
```

Nav: usar `class="site-nav"` + el botón `.navbar-toggle` (ver el markup de
Bajas / Mantenimiento). `.navbar` queda como alias en el CSS.

### 6. Wrappers de la consola

`configuracion.php` en la raíz del sistema:

```php
<?php
require_once 'config.php';
require_once 'auth.php';
require_admin();                       // Mantenimiento: es_admin() por allowlist
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/ui/theme/tema.php';
require_once __DIR__ . '/layout.php';
require __DIR__ . '/ui/theme/configuracion.php';
```

`configuracion_procesar.php` en la raíz:

```php
<?php
require_once 'config.php';
require_once 'auth.php';
require_admin();
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/ui/theme/tema.php';
require __DIR__ . '/ui/theme/configuracion_procesar.php';
```

Agregar el enlace "Configuración" en `.header-user` del `layout.php` para admins.

### 7. Carpeta de subidas

```
uploads/tema/.gitkeep
uploads/tema/.htaccess     # Options -Indexes  +  denegar .php
```

y en `.gitignore`:

```
uploads/tema/*
!uploads/tema/.gitkeep
!uploads/tema/.htaccess
```

## Actualizar el estilo en los tres sistemas

```bash
# en humanidades-ui: commitear el cambio y pushear
# luego, en cada sistema:
git submodule update --remote ui
git add ui && git commit -m "Actualiza humanidades-ui"
```

## Parámetros de la consola

Identidad (nombre, subtítulo, favicon) · Colores de marca (primario,
secundario, acento) · Colores de interfaz (fondo, superficie, borde, texto,
títulos) · Colores de estado (éxito, advertencia, error, info) · Tipografía
(combo, tamaño base, escala de títulos) · Forma y densidad (radio de bordes,
densidad) · Logos (header, footer, PDF — preset institucional o subida) ·
Iconos (set FA por defecto) · PDF (pie, mostrar logo) · Pie institucional
(email, dirección). Todo con vista previa en vivo y botón "Restaurar de
fábrica".

## Fuentes

`fonts/` se generó pidiendo a Google Fonts (licencia **OFL**) los `.woff2` de
Barlow 400/600/700, Montserrat 400/500/600/700 e Inter 400/500/600/700, y
reescribiendo las URLs a rutas locales. Para regenerar, repetir ese pedido
`css2` y volver a apuntar los `src:` a los nombres de archivo locales.
