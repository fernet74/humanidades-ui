<?php
/**
 * configuracion_procesar.php — Guarda los cambios de la consola de tema.
 * -----------------------------------------------------------------------------
 * NO se llama directo: cada sistema tiene un wrapper en su raíz que hace
 *   require 'config.php'; require 'auth.php'; require_admin();
 *   require 'ui/theme/tema.php'; require 'ui/theme/configuracion_procesar.php';
 *
 * Depende del anfitrión:  $conn (mysqli), csrf_verificar(), y opcionalmente
 * registrar_log().  Escribe/actualiza filas en la tabla `configuracion`.
 */

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    exit('configuracion_procesar.php: falta $conn (mysqli).');
}

/** Vuelve a la consola con un mensaje. */
function tema_volver(string $msg = '', string $err = ''): void {
    $qs = array_filter(['msg' => $msg, 'err' => $err]);
    header('Location: configuracion.php' . ($qs ? '?' . http_build_query($qs) : ''));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    tema_volver('', 'Método no permitido.');
}
if (function_exists('csrf_verificar') && !csrf_verificar($_POST['csrf'] ?? null)) {
    tema_volver('', 'La sesión del formulario expiró. Volvé a intentarlo.');
}

/** INSERT … ON DUPLICATE KEY UPDATE sobre la tabla clave/valor. */
function tema_guardar(mysqli $conn, string $clave, string $valor): void {
    $stmt = $conn->prepare("INSERT INTO configuracion (clave, valor) VALUES (?, ?)
                            ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
    $stmt->bind_param('ss', $clave, $valor);
    $stmt->execute();
    $stmt->close();
}

/**
 * Procesa la subida opcional de un archivo (logo / favicon). Devuelve la ruta
 * guardada, o null si no se subió nada. Corta con error si la extensión no va.
 */
function tema_procesar_upload(string $campo, array $exts_ok): ?string {
    if (!isset($_FILES[$campo]) || $_FILES[$campo]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $f = $_FILES[$campo];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        tema_volver('', 'Error al subir "' . $campo . '".');
    }
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $exts_ok, true)) {
        tema_volver('', 'Tipo de archivo no permitido para "' . $campo . '". Use: ' . implode(', ', $exts_ok) . '.');
    }
    $dir = 'uploads/tema/';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        tema_volver('', 'No se pudo crear la carpeta uploads/tema/.');
    }
    $destino = $dir . $campo . '_' . time() . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $destino)) {
        tema_volver('', 'Error al mover el archivo subido de "' . $campo . '".');
    }
    return $destino;
}

// ── Restaurar valores de fábrica ────────────────────────────────────────────
if (isset($_POST['restaurar'])) {
    @$conn->query("DELETE FROM configuracion");
    if (function_exists('registrar_log')) {
        registrar_log($conn, 'restaurar', 'configuracion', null, 'Restauró la configuración de tema a los valores de fábrica.');
    }
    tema_volver('Configuración restaurada a los valores de fábrica.');
}

// ── Guardado campo por campo, según la metadata de tema_campos() ────────────
$defaults = tema_defaults();
$guardados = 0;

foreach (tema_campos() as $nombre => $meta) {
    switch ($meta['tipo']) {

        case 'text':
            $val = trim((string)($_POST[$nombre] ?? ''));
            if ($val === '') $val = $defaults[$nombre];       // no permitir vaciar identidad/pie
            tema_guardar($conn, $nombre, $val);
            $guardados++;
            break;

        case 'textarea':
            $val = trim((string)($_POST[$nombre] ?? ''));
            $val = str_replace("\r\n", "\n", $val);
            tema_guardar($conn, $nombre, $val);
            $guardados++;
            break;

        case 'color':
            $val = strtoupper(trim((string)($_POST[$nombre] ?? '')));
            if (!preg_match('/^#[0-9A-F]{6}$/', $val)) {
                tema_volver('', 'El campo "' . $meta['label'] . '" debe ser un color hexadecimal, ej: ' . $defaults[$nombre] . '.');
            }
            tema_guardar($conn, $nombre, $val);
            $guardados++;
            break;

        case 'select':
            $val = (string)($_POST[$nombre] ?? '');
            $validas = array_keys(tema_opciones($meta['opciones']));
            if (!in_array($val, $validas, true)) $val = $defaults[$nombre];
            tema_guardar($conn, $nombre, $val);
            $guardados++;
            break;

        case 'checkbox':
            tema_guardar($conn, $nombre, isset($_POST[$nombre]) ? '1' : '0');
            $guardados++;
            break;

        case 'logo':
            // 1) archivo subido tiene prioridad
            $exts = array_map('trim', explode(',', str_replace('.', '', $meta['accept'] ?? 'svg,png,jpg,jpeg')));
            $subido = tema_procesar_upload($nombre, $exts);
            if ($subido !== null) {
                tema_guardar($conn, $nombre, $subido);
                $guardados++;
                break;
            }
            // 2) preset elegido del selector
            $preset = (string)($_POST[$nombre . '_preset'] ?? '');
            if ($preset !== '' && array_key_exists($preset, TEMA_LOGOS_PRESET)) {
                tema_guardar($conn, $nombre, $preset);
                $guardados++;
                break;
            }
            // 3) "quitar" (solo favicon)
            if ($nombre === 'favicon' && isset($_POST['favicon_quitar'])) {
                tema_guardar($conn, 'favicon', '');
                $guardados++;
            }
            // 4) si no, se conserva el valor actual
            break;
    }
}

if (function_exists('registrar_log')) {
    registrar_log($conn, 'actualizar', 'configuracion', null, "Actualizó la configuración de tema ($guardados campos).");
}

tema_volver('Configuración guardada correctamente.');
