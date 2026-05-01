<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Si no hay sesión válida, patada al login
if (empty($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// 2. Regenerar ID de sesión periódicamente para mayor seguridad
if (empty($_SESSION['last_regen']) || time() - $_SESSION['last_regen'] > 300) {
    session_regenerate_id(true);
    $_SESSION['last_regen'] = time();
}

// 3. PROTOCOLO ANTI-BACK BUTTON (Destrucción de Caché)
// Esto obliga al navegador a no guardar la página en el historial local.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Fecha en el pasado
?>