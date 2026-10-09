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
  membrete.php                Membrete institucional para documentos imprimibles
                              / PDF (bandas bordó + logo FH). Sirve para dompdf
                              y para impresión desde el navegador.
  sql/configuracion.sql       CREATE TABLE clave/valor (idempotente).
```

## Membrete de documentos imprimibles

`theme/membrete.php` es el estándar único para cualquier documento que se
imprima o exporte a PDF en los sistemas de la Facultad (actas de Bajas,
listados e informes de Inventario, PDF de planos, …). Dibuja dos bandas bordó
a lo ancho de la página — una franja fina arriba y una banda abajo con el
rótulo del departamento y el logo FH en blanco. No usa `background-image` (los
navegadores no lo imprimen de forma confiable): son elementos reales del DOM,
en **dos modos** con comportamiento distinto:

* **`'pdf'`** (dompdf): bandas con `position: fixed` + una regla `@page` con
  los márgenes (`MEMBRETE_MARGEN_PDF`) — dompdf las repite en **todas** las
  páginas del PDF a partir de un único `membrete_bandas('pdf')` emitido al
  inicio del `<body>`. Como dompdf ancla los `fixed` a la caja de contenido
  (adentro de los márgenes de `@page`), no al borde del papel, se compensan
  con offsets negativos iguales a esos márgenes (`MEMBRETE_OFFSET_TOP/BOTTOM`).
* **`'web'`** (impresión desde el navegador, `window.print()`): bandas con
  `position: absolute`, **dentro de cada contenedor de página** (que debe ser
  `position: relative` — una página = un contenedor, p. ej. `.acta-page`), así
  que `membrete_bandas('web')` se emite en el loop, una vez por página. Con
  `position: fixed` el navegador las clava al viewport en la vista previa (se
  ven flotando en los bordes de la ventana en vez de en cada hoja) — por eso
  `'web'` usa `absolute` y no `fixed` como `'pdf'`.

```php
require_once __DIR__ . '/ui/theme/membrete.php';

// en el <head>:
echo membrete_estilos('pdf');   // 'pdf' = dompdf  ·  'web' = navegador (default)

// 'pdf': UNA sola vez, como primeros elementos del <body>.
echo membrete_bandas('pdf');

// 'web': dentro de CADA contenedor de página, en el loop que arma las páginas.
echo membrete_bandas('web');
```

El contenido debe dejar lugar para las bandas:

* **dompdf:** `@page { margin: <?= MEMBRETE_MARGEN_PDF ?> }` (constante exportada
  por el módulo).
* **navegador:** `padding-top` / `padding-bottom` en el contenedor de cada
  página (p. ej. `.acta-page`).

El color sale de `tema('color_primario')` y el rótulo de `tema('pdf_pie_texto')`.
El logo de la banda es fijo (`logos/fh-logo-blanco.*` — PNG para dompdf, SVG
para el navegador), no el logo configurable del header.

## Vistas de detalle de solo lectura

Para pantallas tipo "ver" (el detalle de un registro, sin edición — p. ej.
`ver.php` en Inventario de Red o Bajas Patrimoniales), `humanidades.css` trae
`.info-grid`/`.info-field`: mismo grid que `.form-grid`/`.form-group` (usados
en los formularios de alta/edición) pero para mostrar etiqueta + valor en vez
de un control editable.

```html
<div class="info-grid info-grid-3">
    <div class="info-field"><label>IP</label><div><code>10.0.1.50</code></div></div>
    <div class="info-field"><label>Equipo</label><div>PC-ADMIN-01</div></div>
</div>
```

`info-grid-2` / `info-grid-3` / `info-grid-4` (como `form-grid-*`) fijan la
cantidad de columnas y colapsan a menos en pantallas chicas.

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
(email, dirección). Todo con vista previa en vivo y botón "Restaurar" (en el
color primario), que vuelve a los valores predeterminados.

## Fuentes

`fonts/` se generó pidiendo a Google Fonts (licencia **OFL**) los `.woff2` de
Barlow 400/600/700, Montserrat 400/500/600/700 e Inter 400/500/600/700, y
reescribiendo las URLs a rutas locales. Para regenerar, repetir ese pedido
`css2` y volver a apuntar los `src:` a los nombres de archivo locales.
