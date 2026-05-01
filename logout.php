<?php
/**
 * TERMINATOR LOGOUT: JUDGMENT DAY EDITION (T-1000 UPGRADE)
 * Blindaje total: CSRF, Database Wipe selectivo, Cache Killing & Logging.
 */

require_once 'conexion.php'; // Tu conexión a la DB

// Iniciar sesión solo si no hay una activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. VERIFICACIÓN DE IDENTIDAD (Anti-CSRF GLOBAL)
// Solo se ejecuta si el token enviado coincide con el de la sesión
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['token']) || $_POST['token'] !== ($_SESSION['csrf_token'] ?? '')) {
    // Si alguien intenta una acción falsa o accede por URL directamente, registramos el ataque
    error_log("POSIBLE ATAQUE CSRF detectado desde la IP: " . $_SERVER['REMOTE_ADDR']);
    header('Location: index.php?error=security_breach');
    exit;
}
try {
    // Usamos 'usuario_id' tal como lo definiste en tu login.php
    $user_id = $_SESSION['usuario_id'] ?? null;
    $current_session_id = session_id();

    // 2. EXTERMINIO EN BASE DE DATOS (Destrucción Quirúrgica)
    // Borramos SOLO la sesión actual, permitiendo que siga activo en otros dispositivos
    if ($user_id && $current_session_id) {
        $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE session_id = :session_id");
        $stmt->execute(['session_id' => $current_session_id]);
        
        // Opcional: Registrar la salida en un log de auditoría
        $log = $pdo->prepare("INSERT INTO audit_logs (user_id, action, ip) VALUES (?, 'logout', ?)");
        $log->execute([$user_id, $_SERVER['REMOTE_ADDR']]);
    }

    // 3. LIMPIEZA DE MEMORIA VOLÁTIL
    $_SESSION = [];

    // 4. DESTRUCCIÓN DE LA COOKIE (Físicamente eliminada del disco del cliente)
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), 
            '', 
            time() - 86400, // Un día en el pasado
            $params["path"], 
            $params["domain"], 
            $params["secure"], 
            $params["httponly"]
        );
    }

    // 5. ANIQUILACIÓN DE LA SESIÓN EN SERVIDOR
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }

    // 6. BLOQUEO DE MEMORIA DE NAVEGADOR (Anti-Back Button)
    // Esto obliga al navegador a pedir la página al servidor siempre
    header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
    header("Pragma: no-cache"); // HTTP 1.0
    header("Expires: 0"); // Proxies

    // 7. REDIRECCIÓN CON MENSAJE DE ÉXITO
    header('Location: login.php?msg=session_terminated');
    exit;

} catch (Exception $e) {
    // Si algo falla, forzamos el cierre suavemente por seguridad
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    error_log("Error en Logout: " . $e->getMessage());
    header('Location: login.php?msg=forced_termination');
    exit;
}
?>