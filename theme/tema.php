<?php
/**
 * tema.php — Módulo de tema institucional compartido (Facultad de Humanidades, UNMdP)
 * -----------------------------------------------------------------------------------
 * Fuente de verdad de la consola de configuración. Se incluye desde el bootstrap
 * de cada sistema:  require_once __DIR__ . '/ui/theme/tema.php';
 *
 * Requisitos del sistema anfitrión:
 *   - Una conexión mysqli en $conn (global).  Si no existe, o si la tabla
 *     `configuracion` todavía no fue creada, tema() devuelve los valores de
 *     fábrica (TEMA_DEFAULTS) y nada se rompe.
 *   - Correr una vez  ui/theme/sql/configuracion.sql  en la base del sistema.
 *
 * Personalización por sistema (definir ANTES del require, opcional):
 *   define('TEMA_UI_BASE', 'ui');                 // ruta web al submódulo
 *   define('TEMA_NOMBRE_DEFECTO', 'Inventario de Red');
 *   define('TEMA_SUBTITULO_DEFECTO', 'Facultad de Humanidades — DSI');
 */

// ── Rutas y textos por defecto que puede fijar el anfitrión ──────────────────
if (!defined('TEMA_UI_BASE'))          define('TEMA_UI_BASE', 'ui');
if (!defined('TEMA_NOMBRE_DEFECTO'))   define('TEMA_NOMBRE_DEFECTO', 'Sistema de Gestión');
if (!defined('TEMA_SUBTITULO_DEFECTO'))define('TEMA_SUBTITULO_DEFECTO', 'Facultad de Humanidades — Departamento de Servicios Informáticos');

/** Ruta web (relativa a la página) a la carpeta del submódulo, sin barra final. */
function tema_ui_base(): string {
    return rtrim(TEMA_UI_BASE, '/');
}

// ══════════════════════════════════════════════════════════════════════════════
//  CATÁLOGOS (opciones curadas, no texto libre)
// ══════════════════════════════════════════════════════════════════════════════

/** Combinaciones de tipografía. Las 3 familias están self-host en ui/fonts/. */
const TEMA_FUENTES = [
    'barlow_montserrat' => [
        'etiqueta' => 'Barlow + Montserrat (Manual de Imagen FH)',
        'titulos'  => "'Barlow', 'Segoe UI', Arial, sans-serif",
        'texto'    => "'Montserrat', 'Segoe UI', Georgia, serif",
    ],
    'barlow_inter' => [
        'etiqueta' => 'Barlow + Inter',
        'titulos'  => "'Barlow', 'Segoe UI', Arial, sans-serif",
        'texto'    => "'Inter', 'Segoe UI', system-ui, sans-serif",
    ],
    'inter' => [
        'etiqueta' => 'Inter (uniforme)',
        'titulos'  => "'Inter', 'Segoe UI', system-ui, sans-serif",
        'texto'    => "'Inter', 'Segoe UI', system-ui, sans-serif",
    ],
];

/** Escala de títulos → multiplicador aplicado a h1..h3 (--heading-scale). */
const TEMA_ESCALAS = [
    'compacta' => ['etiqueta' => 'Compacta', 'valor' => '0.9'],
    'normal'   => ['etiqueta' => 'Normal',   'valor' => '1'],
    'amplia'   => ['etiqueta' => 'Amplia',   'valor' => '1.12'],
];

/** Radio de bordes → (--radius, --radius-lg). */
const TEMA_RADIOS = [
    'recto'      => ['etiqueta' => 'Recto (0)',        'radius' => '0',    'radius_lg' => '0'],
    'normal'     => ['etiqueta' => 'Normal (8 / 12)',  'radius' => '8px',  'radius_lg' => '12px'],
    'redondeado' => ['etiqueta' => 'Redondeado (12 / 16)', 'radius' => '12px', 'radius_lg' => '16px'],
];

/** Densidad → multiplicador de padding (--density). */
const TEMA_DENSIDADES = [
    'compacta' => ['etiqueta' => 'Compacta', 'valor' => '0.85'],
    'normal'   => ['etiqueta' => 'Normal',   'valor' => '1'],
    'comoda'   => ['etiqueta' => 'Cómoda',   'valor' => '1.15'],
];

/** Set de iconos Font Awesome por defecto para los <i class="fa-…"> sin prefijo. */
const TEMA_ICON_SETS = [
    'solid'   => ['etiqueta' => 'Font Awesome Solid',   'clase' => 'fas'],
    'regular' => ['etiqueta' => 'Font Awesome Regular',  'clase' => 'far'],
];

/** Logos institucionales incluidos en ui/logos/ (para el selector de la consola). */
const TEMA_LOGOS_PRESET = [
    'fh-logo-blanco.svg'    => 'FH — logo blanco (sobre bordo)',
    'fh-logo-color.svg'     => 'FH — logo color',
    'fh-logo-color.png'     => 'FH — logo color (PNG, para PDF)',
    'fh-simbolo-color.svg'  => 'FH — símbolo color',
    'unmdp-blanco.svg'      => 'UNMdP — blanco',
    'unmdp-negro.svg'       => 'UNMdP — negro',
];

// ══════════════════════════════════════════════════════════════════════════════
//  CAMPOS DE LA CONSOLA — metadata única que manejan configuracion.php
//  (render) y configuracion_procesar.php (validación + guardado).
//  tipo: text | textarea | color | select | checkbox | logo
// ══════════════════════════════════════════════════════════════════════════════
function tema_campos(): array {
    return [
        // Identidad
        'nombre_sistema' => ['grupo' => 'Identidad', 'tipo' => 'text',     'label' => 'Nombre del sistema'],
        'subtitulo'      => ['grupo' => 'Identidad', 'tipo' => 'text',     'label' => 'Subtítulo / bajada'],
        'favicon'        => ['grupo' => 'Identidad', 'tipo' => 'logo',     'label' => 'Favicon', 'accept' => '.ico,.png,.svg'],
        // Marca
        'color_primario'   => ['grupo' => 'Colores de marca', 'tipo' => 'color', 'label' => 'Primario'],
        'color_secundario' => ['grupo' => 'Colores de marca', 'tipo' => 'color', 'label' => 'Secundario'],
        'color_acento'     => ['grupo' => 'Colores de marca', 'tipo' => 'color', 'label' => 'Acento (foco / iconos)'],
        // Interfaz
        'color_fondo'      => ['grupo' => 'Colores de interfaz', 'tipo' => 'color', 'label' => 'Fondo de página'],
        'color_superficie' => ['grupo' => 'Colores de interfaz', 'tipo' => 'color', 'label' => 'Superficie (tarjetas)'],
        'color_borde'      => ['grupo' => 'Colores de interfaz', 'tipo' => 'color', 'label' => 'Bordes'],
        'color_texto'      => ['grupo' => 'Colores de interfaz', 'tipo' => 'color', 'label' => 'Texto'],
        'color_titulos'    => ['grupo' => 'Colores de interfaz', 'tipo' => 'color', 'label' => 'Títulos'],
        // Estado
        'color_exito'       => ['grupo' => 'Colores de estado', 'tipo' => 'color', 'label' => 'Éxito'],
        'color_advertencia' => ['grupo' => 'Colores de estado', 'tipo' => 'color', 'label' => 'Advertencia'],
        'color_error'       => ['grupo' => 'Colores de estado', 'tipo' => 'color', 'label' => 'Error'],
        'color_info'        => ['grupo' => 'Colores de estado', 'tipo' => 'color', 'label' => 'Información'],
        // Tipografía
        'fuente_combo'   => ['grupo' => 'Tipografía', 'tipo' => 'select', 'label' => 'Combinación de fuentes', 'opciones' => 'fuentes'],
        'tamano_base'    => ['grupo' => 'Tipografía', 'tipo' => 'select', 'label' => 'Tamaño base', 'opciones' => 'tamanos'],
        'escala_titulos' => ['grupo' => 'Tipografía', 'tipo' => 'select', 'label' => 'Escala de títulos', 'opciones' => 'escalas'],
        // Forma y densidad
        'radio_bordes' => ['grupo' => 'Forma y densidad', 'tipo' => 'select', 'label' => 'Radio de bordes', 'opciones' => 'radios'],
        'densidad'     => ['grupo' => 'Forma y densidad', 'tipo' => 'select', 'label' => 'Densidad',        'opciones' => 'densidades'],
        // Logos
        'logo_header' => ['grupo' => 'Logos', 'tipo' => 'logo', 'label' => 'Logo del header (sobre fondo primario)', 'accept' => '.svg,.png,.jpg,.jpeg', 'preset' => true],
        'logo_footer' => ['grupo' => 'Logos', 'tipo' => 'logo', 'label' => 'Logo del footer',  'accept' => '.svg,.png,.jpg,.jpeg', 'preset' => true],
        'logo_pdf'    => ['grupo' => 'Logos', 'tipo' => 'logo', 'label' => 'Logo para PDF (PNG/JPG)', 'accept' => '.png,.jpg,.jpeg', 'preset' => true],
        // Iconos
        'icono_set' => ['grupo' => 'Iconos', 'tipo' => 'select', 'label' => 'Set por defecto', 'opciones' => 'icon_sets'],
        // PDF
        'pdf_pie_texto'    => ['grupo' => 'PDF', 'tipo' => 'text',     'label' => 'Texto de pie de página'],
        'pdf_mostrar_logo' => ['grupo' => 'PDF', 'tipo' => 'checkbox', 'label' => 'Mostrar logo en los PDF'],
        // Pie institucional
        'footer_email'  => ['grupo' => 'Pie institucional', 'tipo' => 'text',     'label' => 'Email de contacto'],
        'footer_lineas' => ['grupo' => 'Pie institucional', 'tipo' => 'textarea', 'label' => 'Líneas de dirección (una por renglón)'],
    ];
}

/** Devuelve el catálogo de opciones para un campo select. */
function tema_opciones(string $nombre): array {
    switch ($nombre) {
        case 'fuentes':    return array_map(fn($f) => $f['etiqueta'], TEMA_FUENTES);
        case 'escalas':    return array_map(fn($e) => $e['etiqueta'], TEMA_ESCALAS);
        case 'radios':     return array_map(fn($r) => $r['etiqueta'], TEMA_RADIOS);
        case 'densidades': return array_map(fn($d) => $d['etiqueta'], TEMA_DENSIDADES);
        case 'icon_sets':  return array_map(fn($s) => $s['etiqueta'], TEMA_ICON_SETS);
        case 'tamanos':    return ['13' => '13 px', '14' => '14 px (recomendado)', '15' => '15 px', '16' => '16 px'];
    }
    return [];
}

// ══════════════════════════════════════════════════════════════════════════════
//  VALORES POR DEFECTO — reproducen el aspecto de fábrica (Manual de Imagen FH)
// ══════════════════════════════════════════════════════════════════════════════
function tema_defaults(): array {
    return [
        'nombre_sistema'   => TEMA_NOMBRE_DEFECTO,
        'subtitulo'        => TEMA_SUBTITULO_DEFECTO,
        'favicon'          => '',

        'color_primario'   => '#821632',
        'color_secundario' => '#E99C00',
        'color_acento'     => '#E99C00',

        'color_fondo'      => '#F8F9FA',
        'color_superficie' => '#FFFFFF',
        'color_borde'      => '#E5E7EB',
        'color_texto'      => '#4B5563',
        'color_titulos'    => '#04304D',

        'color_exito'       => '#10B981',
        'color_advertencia' => '#F59E0B',
        'color_error'       => '#E51717',
        'color_info'        => '#0F6499',

        'fuente_combo'     => 'barlow_montserrat',
        'tamano_base'      => '14',
        'escala_titulos'   => 'normal',
        'radio_bordes'     => 'normal',
        'densidad'         => 'normal',

        'logo_header'      => 'fh-logo-blanco.svg',
        'logo_footer'      => 'unmdp-blanco.svg',
        'logo_pdf'         => 'fh-logo-color.png',
        'icono_set'        => 'solid',

        'pdf_pie_texto'    => 'Departamento de Servicios Informáticos',
        'pdf_mostrar_logo' => '1',

        'footer_email'     => 'informaticafh@mdp.edu.ar',
        'footer_lineas'    => "Universidad Nacional de Mar del Plata\nFacultad de Humanidades\nDepartamento de Servicios Informáticos",
    ];
}

// ══════════════════════════════════════════════════════════════════════════════
//  LECTURA DE VALORES VIGENTES  (defaults + fila por fila de la tabla)
// ══════════════════════════════════════════════════════════════════════════════
function tema_todos(): array {
    global $conn;
    static $cache = null;
    if ($cache !== null) return $cache;

    $cache = tema_defaults();
    if (isset($conn) && $conn instanceof mysqli) {
        // @: la tabla puede no existir todavía (no se corrió configuracion.sql)
        $result = @$conn->query("SELECT clave, valor FROM configuracion");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                if ($row['valor'] !== null && $row['valor'] !== '') {
                    $cache[$row['clave']] = $row['valor'];
                }
            }
        }
    }
    return $cache;
}

/** Un valor puntual (ver tema_defaults() para las claves). */
function tema(string $clave): string {
    return tema_todos()[$clave] ?? '';
}

/** Datos de la combinación de tipografía activa. */
function tema_fuente(): array {
    $clave = tema('fuente_combo');
    return TEMA_FUENTES[$clave] ?? TEMA_FUENTES['barlow_montserrat'];
}

/** Clase FA por defecto ('fas' o 'far') según el set elegido. */
function tema_icono_clase(): string {
    $s = tema('icono_set');
    return TEMA_ICON_SETS[$s]['clase'] ?? 'fas';
}

// ── Resolución de logos ─────────────────────────────────────────────────────
//   'fh-logo.svg'            → <ui>/logos/fh-logo.svg          (preset)
//   'img/x.svg'              → img/x.svg                       (ruta del sistema, retrocompat)
//   'uploads/tema/x.png'     → uploads/tema/x.png              (subido desde la consola)
//   'https://…' / '/abs'     → tal cual

/** URL (para usar en el HTML) del logo guardado en $clave. */
function tema_logo_url(string $clave): string {
    $v = tema($clave);
    if ($v === '') return '';
    if (preg_match('#^(https?:)?//#', $v) || $v[0] === '/') return $v;
    if (strpos($v, '/') !== false) return $v;                 // ruta relativa del sistema
    return tema_ui_base() . '/logos/' . $v;                    // preset del submódulo
}

/** Ruta de archivo en disco del logo (para dompdf / lectura server-side). */
function tema_logo_fs(string $clave): string {
    $v = tema($clave);
    if ($v === '') return '';
    if (strpos($v, '/') === false) return __DIR__ . '/../logos/' . $v;   // preset
    if ($v[0] === '/' || preg_match('#^[A-Za-z]:[\\\\/]#', $v)) return $v; // absoluta
    return $v;                                                            // relativa al script llamador
}

// ══════════════════════════════════════════════════════════════════════════════
//  SALIDA HTML
// ══════════════════════════════════════════════════════════════════════════════

/** Bloque <style> con los tokens semánticos pisados por la consola. */
function tema_css_vars(): string {
    $t   = tema_todos();
    $d   = tema_defaults();
    $f   = tema_fuente();
    $esc = TEMA_ESCALAS[$t['escala_titulos']]['valor'] ?? '1';
    $rad = TEMA_RADIOS[$t['radio_bordes']]             ?? TEMA_RADIOS['normal'];
    $den = TEMA_DENSIDADES[$t['densidad']]['valor']    ?? '1';
    $tam = preg_match('/^\d{2}$/', (string)$t['tamano_base']) ? $t['tamano_base'] : '14';

    $color = function (string $k) use ($t, $d): string {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', (string)($t[$k] ?? '')) ? $t[$k] : $d[$k];
    };

    $vars = [
        '--color-primary'   => $color('color_primario'),
        '--color-secondary' => $color('color_secundario'),
        '--color-accent'    => $color('color_acento'),
        '--color-bg'        => $color('color_fondo'),
        '--color-surface'   => $color('color_superficie'),
        '--color-border'    => $color('color_borde'),
        '--color-text'      => $color('color_texto'),
        '--color-heading'   => $color('color_titulos'),
        '--color-success'   => $color('color_exito'),
        '--color-warning'   => $color('color_advertencia'),
        '--color-error'     => $color('color_error'),
        '--color-info'      => $color('color_info'),
        '--font-heading'    => $f['titulos'],
        '--font-body'       => $f['texto'],
        '--font-size-base'  => $tam . 'px',
        '--heading-scale'   => $esc,
        '--radius'          => $rad['radius'],
        '--radius-lg'       => $rad['radius_lg'],
        '--density'         => $den,
    ];

    $out = ':root{';
    foreach ($vars as $k => $v) {
        $out .= $k . ':' . $v . ';';
    }
    return $out . '}';
}

/**
 * Emite en el <head> todo lo que necesita la plantilla: fuentes self-host,
 * la hoja institucional, Font Awesome (CDN), el favicon y los tokens del tema.
 * Llamar DESPUÉS del <link> de Bootstrap donde éste exista.
 */
function tema_head_tags(): void {
    $base = tema_ui_base();
    $cssv = @filemtime(__DIR__ . '/../humanidades.css') ?: time();
    $fav  = tema_logo_url('favicon');
    ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($base) ?>/fonts/fonts.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($base) ?>/humanidades.css?v=<?= $cssv ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php if ($fav): ?><link rel="icon" href="<?= htmlspecialchars($fav) ?>"><?php endif; ?>
    <style><?= tema_css_vars() ?></style>
    <?php
}
