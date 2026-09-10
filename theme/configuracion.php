<?php
/**
 * configuracion.php — Vista de la consola de configuración de tema.
 * -----------------------------------------------------------------------------
 * NO se llama directo: cada sistema tiene un wrapper en su raíz que hace
 *   require 'config.php'; require 'auth.php'; require_admin();
 *   require 'ui/theme/tema.php'; require 'ui/theme/configuracion.php';
 *
 * Depende del anfitrión:  layout_header()/layout_footer() y, si existe,
 * csrf_token().  Guarda con configuracion_procesar.php (otro wrapper).
 */

$t      = tema_todos();
$campos = tema_campos();
$msg    = $_GET['msg'] ?? '';
$err    = $_GET['err'] ?? '';
$csrf   = function_exists('csrf_token') ? csrf_token() : '';
$icls   = tema_icono_clase();

// Agrupar los campos por 'grupo' conservando el orden de declaración
$grupos = [];
foreach ($campos as $nombre => $meta) {
    $grupos[$meta['grupo']][$nombre] = $meta;
}

// Iconos FA por grupo (decorativos)
$grupo_icono = [
    'Identidad'            => 'fa-heading',
    'Colores de marca'     => 'fa-fill-drip',
    'Colores de interfaz'  => 'fa-palette',
    'Colores de estado'    => 'fa-traffic-light',
    'Tipografía'           => 'fa-font',
    'Forma y densidad'     => 'fa-vector-square',
    'Logos'                => 'fa-image',
    'Iconos'               => 'fa-icons',
    'PDF'                  => 'fa-file-pdf',
    'Pie institucional'    => 'fa-shoe-prints',
];

if (function_exists('layout_header')) {
    layout_header('Configuración del Sistema', 'configuracion');
}
?>

<?php if ($msg): ?>
<div class="alert alert-success"><i class="<?= $icls ?> fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
<?php endif; ?>
<?php if ($err): ?>
<div class="alert alert-error"><i class="<?= $icls ?> fa-exclamation-triangle"></i> <?= htmlspecialchars($err) ?></div>
<?php endif; ?>

<div class="two-column-grid tema-config">
    <div class="card">
        <div class="card-header">
            <h2><i class="<?= $icls ?> fa-sliders"></i> Tema y marca del sistema</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="configuracion_procesar.php" enctype="multipart/form-data" id="tema-form">
                <?php if ($csrf): ?><input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>"><?php endif; ?>

                <?php foreach ($grupos as $grupo => $items): ?>
                <div class="form-section">
                    <div class="form-section-header">
                        <i class="<?= $icls ?> <?= $grupo_icono[$grupo] ?? 'fa-circle' ?>"></i> <?= htmlspecialchars($grupo) ?>
                    </div>
                    <div class="form-section-body">
                        <div class="form-grid form-grid-2">
                            <?php foreach ($items as $nombre => $meta):
                                $val = $t[$nombre] ?? '';
                                $full = in_array($meta['tipo'], ['textarea'], true) || $meta['tipo'] === 'logo';
                            ?>
                            <div class="form-group" <?= $full ? 'style="grid-column:1/-1;"' : '' ?>>
                                <label for="f_<?= $nombre ?>"><?= htmlspecialchars($meta['label']) ?></label>

                                <?php if ($meta['tipo'] === 'text'): ?>
                                    <input type="text" id="f_<?= $nombre ?>" name="<?= $nombre ?>" class="form-control"
                                           value="<?= htmlspecialchars($val) ?>">

                                <?php elseif ($meta['tipo'] === 'textarea'): ?>
                                    <textarea id="f_<?= $nombre ?>" name="<?= $nombre ?>" class="form-control" rows="3"><?= htmlspecialchars($val) ?></textarea>

                                <?php elseif ($meta['tipo'] === 'checkbox'): ?>
                                    <label class="flex-row" style="font-weight:400;">
                                        <input type="checkbox" id="f_<?= $nombre ?>" name="<?= $nombre ?>" value="1" <?= $val === '1' ? 'checked' : '' ?>>
                                        Sí
                                    </label>

                                <?php elseif ($meta['tipo'] === 'color'): ?>
                                    <span class="tema-color">
                                        <input type="color" class="tema-color-picker" data-target="f_<?= $nombre ?>" data-var="<?= $nombre ?>"
                                               value="<?= htmlspecialchars(preg_match('/^#[0-9A-Fa-f]{6}$/', $val) ? $val : '#000000') ?>">
                                        <input type="text" id="f_<?= $nombre ?>" name="<?= $nombre ?>" class="form-control tema-color-text"
                                               value="<?= htmlspecialchars($val) ?>" pattern="^#[0-9A-Fa-f]{6}$" spellcheck="false">
                                    </span>

                                <?php elseif ($meta['tipo'] === 'select'):
                                    $ops = tema_opciones($meta['opciones']); ?>
                                    <select id="f_<?= $nombre ?>" name="<?= $nombre ?>" class="form-control" data-var="<?= $nombre ?>">
                                        <?php foreach ($ops as $k => $et): ?>
                                        <option value="<?= htmlspecialchars($k) ?>" <?= (string)$val === (string)$k ? 'selected' : '' ?>><?= htmlspecialchars($et) ?></option>
                                        <?php endforeach; ?>
                                    </select>

                                <?php elseif ($meta['tipo'] === 'logo'):
                                    $url = tema_logo_url($nombre); ?>
                                    <div class="tema-logo">
                                        <?php if ($url): ?>
                                        <img src="<?= htmlspecialchars($url) ?>" alt="actual"
                                             style="height:38px;background:<?= $nombre === 'logo_header' ? htmlspecialchars($t['color_primario']) : 'transparent' ?>;padding:6px;border-radius:6px;border:1px solid var(--color-border);">
                                        <?php else: ?>
                                        <span class="muted">— sin definir —</span>
                                        <?php endif; ?>
                                        <?php if (!empty($meta['preset'])): ?>
                                        <select name="<?= $nombre ?>_preset" class="form-control" style="margin-top:8px;">
                                            <option value="">— mantener / o subir uno propio —</option>
                                            <?php foreach (TEMA_LOGOS_PRESET as $file => $et): ?>
                                            <option value="<?= htmlspecialchars($file) ?>" <?= $val === $file ? 'selected' : '' ?>><?= htmlspecialchars($et) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php endif; ?>
                                        <input type="file" name="<?= $nombre ?>" class="form-control" accept="<?= htmlspecialchars($meta['accept'] ?? '') ?>" style="margin-top:8px;">
                                        <?php if ($nombre === 'favicon' && $val !== ''): ?>
                                        <label class="flex-row" style="font-weight:400;margin-top:6px;">
                                            <input type="checkbox" name="favicon_quitar" value="1"> Quitar favicon
                                        </label>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="<?= $icls ?> fa-save"></i> Guardar cambios</button>
                    <a href="index.php" class="btn btn-secondary"><i class="<?= $icls ?> fa-times"></i> Cancelar</a>
                    <button type="submit" name="restaurar" value="1" class="btn btn-danger" style="margin-left:auto;"
                            onclick="return confirm('¿Restaurar TODA la configuración de tema a los valores de fábrica del Manual de Imagen?');">
                        <i class="<?= $icls ?> fa-rotate-left"></i> Restaurar de fábrica
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3><i class="<?= $icls ?> fa-eye"></i> Vista previa</h3></div>
        <div class="card-body">
            <p class="muted" style="margin-bottom:12px;">Los cambios de color, fuente, radio y densidad se ven acá al instante. Se aplican a todo el sistema al Guardar.</p>
            <div id="tema-preview" class="tema-preview">
                <div class="tp-header">
                    <img id="tp-logo" src="<?= htmlspecialchars(tema_logo_url('logo_header')) ?>" alt="logo" onerror="this.style.display='none'">
                    <span id="tp-nombre"><?= htmlspecialchars($t['nombre_sistema']) ?></span>
                </div>
                <div class="tp-body">
                    <p class="tp-titulo">Título de ejemplo</p>
                    <p class="tp-texto">Texto de cuerpo. Así se ve el contenido normal de las páginas del sistema, con la tipografía y el tamaño elegidos.</p>
                    <div class="tp-btns">
                        <button type="button" class="btn btn-primary">Acción</button>
                        <button type="button" class="btn btn-secondary">Secundaria</button>
                    </div>
                    <div class="tp-alert tp-alert-success">Operación exitosa</div>
                    <div class="tp-alert tp-alert-warning">Advertencia</div>
                    <div class="tp-alert tp-alert-error">Ocurrió un error</div>
                    <table class="tp-table">
                        <thead><tr><th>Columna</th><th>Estado</th></tr></thead>
                        <tbody>
                            <tr><td>Fila de ejemplo</td><td><span class="tp-badge">Activo</span></td></tr>
                            <tr><td>Otra fila</td><td><span class="tp-badge tp-badge-gray">—</span></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos locales de la consola (no van en la hoja global) */
.tema-color { display:flex; gap:8px; align-items:center; }
.tema-color-picker { width:44px; height:38px; padding:2px; border:1px solid var(--color-border); border-radius:var(--radius); background:var(--color-surface); cursor:pointer; }
.tema-color-text { font-family:var(--font-mono); }
.tema-logo { display:flex; flex-direction:column; align-items:flex-start; }

.tema-preview {
    --tp-primary:<?= htmlspecialchars($t['color_primario']) ?>;
    --tp-bg:<?= htmlspecialchars($t['color_fondo']) ?>;
    --tp-surface:<?= htmlspecialchars($t['color_superficie']) ?>;
    --tp-border:<?= htmlspecialchars($t['color_borde']) ?>;
    --tp-text:<?= htmlspecialchars($t['color_texto']) ?>;
    --tp-heading:<?= htmlspecialchars($t['color_titulos']) ?>;
    --tp-success:<?= htmlspecialchars($t['color_exito']) ?>;
    --tp-warning:<?= htmlspecialchars($t['color_advertencia']) ?>;
    --tp-error:<?= htmlspecialchars($t['color_error']) ?>;
    --tp-radius:8px; --tp-heading-scale:1;
    --tp-font-h:<?= tema_fuente()['titulos'] ?>;
    --tp-font-b:<?= tema_fuente()['texto'] ?>;
    border:1px solid var(--tp-border); border-radius:var(--tp-radius); overflow:hidden; background:var(--tp-bg);
}
.tema-preview .tp-header { background:var(--tp-primary); color:#fff; padding:14px 16px; display:flex; align-items:center; gap:12px; font-family:var(--tp-font-h); font-weight:700; }
.tema-preview .tp-header img { height:28px; }
.tema-preview .tp-body { padding:16px; }
.tema-preview .tp-titulo { font-family:var(--tp-font-h); font-weight:700; color:var(--tp-heading); font-size:calc(18px * var(--tp-heading-scale)); margin-bottom:6px; }
.tema-preview .tp-texto  { font-family:var(--tp-font-b); color:var(--tp-text); font-size:13px; margin-bottom:12px; }
.tema-preview .tp-btns { display:flex; gap:8px; margin-bottom:12px; }
.tema-preview .btn-primary { background:var(--tp-primary); border-color:var(--tp-primary); border-radius:var(--tp-radius); }
.tema-preview .btn-secondary { color:var(--tp-primary); border-color:var(--tp-primary); border-radius:var(--tp-radius); background:var(--tp-surface); }
.tema-preview .tp-alert { border-left:4px solid; border-radius:var(--tp-radius); padding:8px 12px; font-size:12px; margin-bottom:6px; }
.tema-preview .tp-alert-success { border-color:var(--tp-success); background:color-mix(in srgb,var(--tp-success) 12%,#fff); }
.tema-preview .tp-alert-warning { border-color:var(--tp-warning); background:color-mix(in srgb,var(--tp-warning) 12%,#fff); }
.tema-preview .tp-alert-error   { border-color:var(--tp-error);   background:color-mix(in srgb,var(--tp-error) 12%,#fff); }
.tema-preview .tp-table { width:100%; border-collapse:collapse; font-family:var(--tp-font-b); font-size:12px; margin-top:8px; background:var(--tp-surface); }
.tema-preview .tp-table th { text-align:left; padding:8px 10px; border-bottom:2px solid var(--tp-border); text-transform:uppercase; font-size:11px; color:var(--tp-heading); }
.tema-preview .tp-table td { padding:8px 10px; border-bottom:1px solid var(--tp-border); color:var(--tp-text); }
.tema-preview .tp-badge { background:var(--tp-primary); color:#fff; border-radius:20px; padding:2px 10px; font-size:11px; font-weight:600; }
.tema-preview .tp-badge-gray { background:#9CA3AF; }
</style>

<script>
(function () {
    var pv = document.getElementById('tema-preview');
    var FUENTES = <?= json_encode(array_map(fn($f) => ['t' => $f['titulos'], 'b' => $f['texto']], TEMA_FUENTES)) ?>;
    var ESCALAS = <?= json_encode(array_map(fn($e) => $e['valor'], TEMA_ESCALAS)) ?>;
    var RADIOS  = <?= json_encode(array_map(fn($r) => $r['radius'], TEMA_RADIOS)) ?>;

    var MAP = {
        color_primario:'--tp-primary', color_fondo:'--tp-bg', color_superficie:'--tp-surface',
        color_borde:'--tp-border', color_texto:'--tp-text', color_titulos:'--tp-heading',
        color_exito:'--tp-success', color_advertencia:'--tp-warning', color_error:'--tp-error'
    };

    // color pickers <-> texto
    document.querySelectorAll('.tema-color-picker').forEach(function (pick) {
        var text = document.getElementById(pick.dataset.target);
        pick.addEventListener('input', function () { text.value = pick.value.toUpperCase(); apply(); });
        text.addEventListener('input', function () {
            if (/^#[0-9A-Fa-f]{6}$/.test(text.value)) pick.value = text.value;
            apply();
        });
    });
    // selects que afectan el preview
    ['fuente_combo', 'escala_titulos', 'radio_bordes'].forEach(function (id) {
        var el = document.getElementById('f_' + id);
        if (el) el.addEventListener('change', apply);
    });
    var nombre = document.getElementById('f_nombre_sistema');
    if (nombre) nombre.addEventListener('input', function () {
        document.getElementById('tp-nombre').textContent = nombre.value || '—';
    });

    function apply() {
        for (var k in MAP) {
            var el = document.getElementById('f_' + k);
            if (el && /^#[0-9A-Fa-f]{6}$/.test(el.value)) pv.style.setProperty(MAP[k], el.value);
        }
        var fc = document.getElementById('f_fuente_combo');
        if (fc && FUENTES[fc.value]) {
            pv.style.setProperty('--tp-font-h', FUENTES[fc.value].t);
            pv.style.setProperty('--tp-font-b', FUENTES[fc.value].b);
        }
        var es = document.getElementById('f_escala_titulos');
        if (es && ESCALAS[es.value]) pv.style.setProperty('--tp-heading-scale', ESCALAS[es.value]);
        var rb = document.getElementById('f_radio_bordes');
        if (rb && RADIOS[rb.value] !== undefined) pv.style.setProperty('--tp-radius', RADIOS[rb.value]);
    }
    apply();
})();
</script>

<?php
if (function_exists('layout_footer')) {
    layout_footer();
}
