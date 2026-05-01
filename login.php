<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Si ya está logueado, fuera de aquí
if (!empty($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

require 'conexion.php';
$error = '';
$mensaje_exito = '';

// 2. Captura de mensajes desde logout.php o index.php
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'session_terminated') {
        $mensaje_exito = 'Sesión cerrada correctamente. ¡Hasta pronto!';
    } elseif ($_GET['msg'] === 'forced_termination') {
        $mensaje_exito = 'Sesión terminada por protocolo de seguridad.';
    }
}

if (isset($_GET['error'])) {
    if ($_GET['error'] === 'security_breach') {
        $error = 'Acceso denegado: Se detectó una acción no autorizada.';
    }
}

// 3. Procesamiento del Formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_input  = trim($_POST['usuario']  ?? '');
    $password_input = trim($_POST['password'] ?? '');
    $ip_cliente     = $_SERVER['REMOTE_ADDR'];

    // A) LIMPIEZA AUTOMÁTICA: Borramos los intentos fallidos de hace más de 15 minutos
    $pdo->query("DELETE FROM login_attempts WHERE fecha < (NOW() - INTERVAL 10 SECOND)");

    // B) VERIFICACIÓN DE BLOQUEO: Contamos cuántas veces ha fallado esta IP recientemente
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ?");
    $stmt_check->execute([$ip_cliente]);
    $intentos = $stmt_check->fetchColumn();

    if ($intentos >= 3) {
        // Si falló 3 veces o más, bloqueamos el acceso
        $error = 'Demasiados intentos fallidos. Por seguridad, tu IP está bloqueada por 15 minutos.';
    } elseif ($usuario_input === '' || $password_input === '') {
        $error = 'Por favor completa todos los campos.';
    } else {
        // C) BÚSQUEDA DEL USUARIO
        $stmt = $pdo->prepare("SELECT id, usuario, password FROM usuarios WHERE usuario = ? LIMIT 1");
        $stmt->execute([$usuario_input]);
        $row = $stmt->fetch();

        if ($row && password_verify($password_input, $row['password'])) {
            
            // --- INICIO PROTOCOLO DE SEGURIDAD ---
            session_regenerate_id(true); 
            
            $_SESSION['usuario_id']  = $row['id'];
            $_SESSION['usuario']     = $row['usuario'];
            $_SESSION['last_regen']  = time();
            $_SESSION['csrf_token']  = bin2hex(random_bytes(32)); // Renombrado a csrf_token (nos servirá para el Paso 2)

            // Registrar la sesión activa
            try {
                $stmt_session = $pdo->prepare("INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent) VALUES (?, ?, ?, ?)");
                $stmt_session->execute([
                    $row['id'], session_id(), $ip_cliente, $_SERVER['HTTP_USER_AGENT']
                ]);
            } catch (Exception $e) {
                error_log("Error DB Sesión: " . $e->getMessage());
            }

            // D) ÉXITO: Limpiamos su historial de fallos porque ya entró correctamente
            $stmt_clear = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
            $stmt_clear->execute([$ip_cliente]);
            // --- FIN PROTOCOLO ---

            header('Location: index.php');
            exit;
        } else {
            // E) FALLO: Registramos el intento fallido en la base de datos
            $stmt_fail = $pdo->prepare("INSERT INTO login_attempts (ip_address, usuario) VALUES (?, ?)");
            $stmt_fail->execute([$ip_cliente, $usuario_input]);

            $intentos_restantes = 2 - $intentos; // 2 porque ya falló 1 vez de 3 permitidas
            if ($intentos_restantes > 0) {
                $error = "Usuario o contraseña incorrectos. Te quedan $intentos_restantes intento(s).";
            } else {
                $error = "Usuario o contraseña incorrectos. Tu cuenta ha sido bloqueada temporalmente.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreshStock · Iniciar Sesión</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Iconos de Bootstrap corregidos para que luzcan bien -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
      body { display: block; }
      /* Estilos rápidos para alertas en caso de que no estén en tu style.css */
      .flash-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
      .flash-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
      .flash { margin-bottom: 20px; padding: 12px; border-radius: 8px; font-size: 0.85rem; display: flex; align-items: center; gap: 8px; }
    </style>
</head>
<body>
<div class="login-page">
  <div class="login-bg"></div>

  <div class="login-card">
    <div class="login-brand">
      <div class="login-logo"><i class="bi bi-box-seam"></i></div>
      <div class="login-title">FreshStock</div>
      <div class="login-sub">Sistema de Control de Inventario</div>
    </div>

    <!-- Alertas de Error -->
    <?php if ($error): ?>
    <div class="flash flash-error">
        <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- Alertas de Éxito/Información -->
    <?php if ($mensaje_exito): ?>
    <div class="flash flash-success">
        <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($mensaje_exito) ?>
    </div>
    <?php endif; ?>

    <form class="login-form" method="POST" autocomplete="off">
      <div class="form-group">
        <label for="usuario"><i class="bi bi-person-circle"></i> Usuario</label>
        <input
          type="text"
          id="usuario"
          name="usuario"
          placeholder="Ingresa tu usuario"
          value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>"
          required
          autofocus
        >
      </div>

      <div class="form-group">
        <label for="password"><i class="bi bi-lock"></i> Contraseña</label>
        <input
          type="password"
          id="password"
          name="password"
          placeholder="••••••••"
          required
        >
      </div>

      <button type="submit" class="btn btn-primary" style="margin-top:8px; width: 100%;">
        Ingresar al sistema <i class="bi bi-arrow-right"></i>
      </button>
    </form>

    <p style="text-align:center;font-size:.75rem;color:var(--txt-muted);margin-top:24px">
      FreshStock v2.0 · Gestión de Perecibles
    </p>
  </div>
</div>
</body>
</html>