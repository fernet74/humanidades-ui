<?php
/**
 * membrete.php — Membrete institucional para documentos imprimibles
 * -----------------------------------------------------------------------------
 * Estándar compartido por todos los sistemas de la Facultad de Humanidades para
 * cualquier documento que se imprima o se exporte a PDF (actas, listados, etc.),
 * ya sea renderizado por dompdf (server-side) o por el navegador (window.print()).
 *
 * Dibuja dos bandas bordó a lo ancho de la página:
 *   · superior: una franja fina.
 *   · inferior: franja con el rótulo del departamento a la izquierda y el
 *     logo de la Facultad (blanco) a la derecha.
 *
 * Ambas con position:fixed, así se repiten en TODAS las páginas — tanto en
 * dompdf como al imprimir desde el navegador. NO se usa background-image
 * porque los navegadores no lo imprimen de forma confiable.
 *
 * Uso:
 *   require_once __DIR__ . '/ui/theme/membrete.php';
 *   // en el <head> (o al inicio del <body>):
 *   echo membrete_estilos();
 *   // como primeros elementos del <body>:
 *   echo membrete_bandas('pdf');   // 'pdf' = dompdf (ruta de archivo del logo)
 *                                  // 'web' = navegador (URL del logo)
 *
 * El contenido de la página debe dejar lugar para las bandas:
 *   · dompdf   → @page { margin: MEMBRETE_MARGEN_PDF }
 *   · navegador→ padding-top / padding-bottom en el contenedor de página
 */

require_once __DIR__ . '/tema.php';

/** Márgenes de @page en dompdf (top right bottom left): dejan aire para las
 *  bandas. El top/bottom deben coincidir con MEMBRETE_OFFSET_* (abajo). */
const MEMBRETE_MARGEN_PDF = '12mm 12mm 20mm 12mm';

/** Alturas de las bandas (mismas para dompdf y navegador). */
const MEMBRETE_ALTO_TOP    = '7mm';
const MEMBRETE_ALTO_BOTTOM = '16mm';

/** dompdf posiciona los `position: fixed` respecto de la caja de contenido
 *  (dentro de los márgenes de @page), no del borde del papel. Estos offsets
 *  negativos —iguales al margen top/bottom de @page— devuelven las bandas al
 *  borde de la hoja. En modo 'web' el navegador ya las ancla al borde. */
const MEMBRETE_OFFSET_TOP    = '12mm';
const MEMBRETE_OFFSET_BOTTOM = '20mm';

/** Color de las bandas: el primario del tema, o el bordó institucional por defecto. */
function membrete_color(): string {
    $c = tema('color_primario');
    return preg_match('/^#[0-9A-Fa-f]{6}$/', $c) ? $c : '#990000';
}

/** Las dos líneas del rótulo de la izquierda de la banda inferior. */
function membrete_rotulo(): array {
    $l1 = tema('pdf_pie_texto') !== '' ? tema('pdf_pie_texto') : 'Departamento de Servicios Informáticos';
    return [$l1, 'Informe técnico'];
}

/**
 * Ruta/URL del logo blanco de la Facultad para la banda inferior.
 * Es un elemento fijo del membrete institucional (no el logo configurable del
 * header): siempre el isologo FH en blanco, que contrasta con la banda bordó.
 *   'pdf' → ruta de archivo absoluta (dompdf; PNG, porque no rasteriza SVG).
 *   'web' → URL (navegador; SVG, más nítido).
 */
function membrete_logo(string $modo): string {
    if ($modo === 'pdf') {
        return realpath(__DIR__ . '/../logos/fh-logo-blanco.png') ?: '';
    }
    return tema_ui_base() . '/logos/fh-logo-blanco.svg';
}

/**
 * <style> con las reglas del membrete (color ya resuelto: sirve tanto para
 * dompdf como para el navegador). En modo 'pdf' además emite la regla @page
 * con los márgenes que dejan lugar a las bandas.
 * $modo: 'pdf' (dompdf) | 'web' (navegador, por defecto).
 */
function membrete_estilos(string $modo = 'web'): string {
    $c = membrete_color();
    $t = MEMBRETE_ALTO_TOP;
    $b = MEMBRETE_ALTO_BOTTOM;

    if ($modo === 'pdf') {
        $page   = '@page { margin: ' . MEMBRETE_MARGEN_PDF . '; }';
        $topPos = 'top: -' . MEMBRETE_OFFSET_TOP . ';';
        $botPos = 'bottom: -' . MEMBRETE_OFFSET_BOTTOM . ';';
    } else {
        $page   = '';
        $topPos = 'top: 0;';
        $botPos = 'bottom: 0;';
    }

    return <<<CSS
<style>
    {$page}
    .fh-membrete-top, .fh-membrete-bottom {
        position: fixed; left: 0; right: 0;
        background: {$c};
        -webkit-print-color-adjust: exact; print-color-adjust: exact; color-adjust: exact;
    }
    .fh-membrete-top    { {$topPos} height: {$t}; }
    .fh-membrete-bottom { {$botPos} height: {$b}; }
    .fh-membrete-bottom table { width: 100%; height: {$b}; border-collapse: collapse; }
    .fh-membrete-bottom td { border: none; vertical-align: middle; color: #fff; padding: 0 14mm; }
    .fh-membrete-dep  { font-size: 8.5px; line-height: 1.3; font-weight: bold; }
    .fh-membrete-logo { height: 11mm; }
</style>
CSS;
}

/**
 * Markup de las dos bandas. Emitir UNA sola vez, como primeros hijos del <body>.
 * $modo: 'pdf' (dompdf) | 'web' (navegador).
 */
function membrete_bandas(string $modo = 'web'): string {
    $logo = membrete_logo($modo);
    [$l1, $l2] = membrete_rotulo();
    $img = $logo !== ''
        ? '<img class="fh-membrete-logo" src="' . htmlspecialchars($logo) . '" alt="Facultad de Humanidades">'
        : '';
    $l1 = htmlspecialchars($l1);
    $l2 = htmlspecialchars($l2);
    return <<<HTML
<div class="fh-membrete-top"></div>
<div class="fh-membrete-bottom"><table><tr>
    <td style="text-align:left"><span class="fh-membrete-dep">{$l1}<br>{$l2}</span></td>
    <td style="text-align:right">{$img}</td>
</tr></table></div>
HTML;
}
