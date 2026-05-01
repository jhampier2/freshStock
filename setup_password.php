<?php
/**
 * FRESHSTOCK — Script de configuración de contraseñas
 */
require 'auth.php';
require 'conexion.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario      = trim($_POST['usuario']       ?? '');
    $nueva_pass   = trim($_POST['nueva_password'] ?? '');
    $confirmar    = trim($_POST['confirmar']       ?? '');

    if ($usuario === '' || $nueva_pass === '') {
        $mensaje = ['tipo' => 'error', 'texto' => 'Completa todos los campos.'];
    } elseif ($nueva_pass !== $confirmar) {
        $mensaje = ['tipo' => 'error', 'texto' => 'Las contraseñas no coinciden.'];
    } elseif (strlen($nueva_pass) < 6) {
        $mensaje = ['tipo' => 'error', 'texto' => 'La contraseña debe tener al menos 6 caracteres.'];
    } else {
        $hash = password_hash($nueva_pass, PASSWORD_DEFAULT);

        // ¿El usuario ya existe?
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $stmt->execute([$usuario]);
        $existe = $stmt->fetch();

        if ($existe) {
            // Actualizar hash
            $pdo->prepare("UPDATE usuarios SET password = ? WHERE usuario = ?")
                ->execute([$hash, $usuario]);
            $mensaje = ['tipo' => 'ok', 'texto' => "<i class=\"bi bi-check-circle\"></i> Contraseña actualizada para «{$usuario}»."];
        } else {
            // Insertar nuevo usuario
            $pdo->prepare("INSERT INTO usuarios (usuario, password) VALUES (?, ?)")
                ->execute([$usuario, $hash]);
            $mensaje = ['tipo' => 'ok', 'texto' => "<i class=\"bi bi-check-circle\"></i> Usuario «{$usuario}» creado correctamente."];
        }
    }
}

// Mostrar usuarios actuales (sin contraseña)
$usuarios = $pdo->query("SELECT id, usuario FROM usuarios ORDER BY id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FreshStock · Configurar Contraseña</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- ── SIDEBAR ───────────────────────────────────────────────────────────── -->
<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon"><i class="bi bi-box"></i></div>
    <div>
      <div class="brand-name">FreshStock</div>
      <div class="brand-sub">Inventario</div>
    </div>
  </div>

  <span class="nav-section">Menú</span>
  <a href="index.php"     class="nav-link"><span class="icon"><i class="bi bi-graph-up"></i></span> Dashboard</a>
  <a href="productos.php" class="nav-link"><span class="icon"><i class="bi bi-box"></i></span> Productos</a>
  <a href="exportar.php"  class="nav-link"><span class="icon"><i class="bi bi-download"></i></span> Exportar CSV</a>
  <a href="setup_password.php" class="nav-link active"><span class="icon"><i class="bi bi-gear"></i></span> Configurar Contraseña</a>

  <div class="sidebar-footer">
    <div class="user-pill">
      <div class="user-avatar"><i class="bi bi-person-circle"></i></div>
      <div class="user-info">
        <div class="user-name"><?= htmlspecialchars($_SESSION['usuario']) ?></div>
        <div class="user-role" style="cursor:pointer;transition:.2s" title="Volver al dashboard" onclick="window.location.href='index.php'">Administrador</div>
      </div>
      <a href="logout.php" class="logout-btn" title="Cerrar sesión"><i class="bi bi-box-arrow-right"></i></a>
    </div>
  </div>
</aside>

<!-- ── MAIN ──────────────────────────────────────────────────────────────── -->
<main class="main">
  <div class="page-header">
    <h1 class="page-title"><i class="bi bi-gear"></i> Configurar Contraseñas</h1>
    <p class="page-subtitle">Crear o actualizar contraseñas de usuarios</p>
  </div>

  <?php if ($mensaje): ?>
  <div class="alert alert-<?= $mensaje['tipo'] === 'ok' ? 'success' : 'danger' ?>" style="margin-bottom:20px">
    <?= $mensaje['texto'] ?>
  </div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

    <!-- Formulario -->
    <div class="section-card">
      <div class="section-head"><span class="section-title">Configurar contraseña</span></div>
      <form method="POST" style="padding:24px;display:flex;flex-direction:column;gap:16px">
        <div class="form-group">
          <label style="display:block;margin-bottom:6px;color:var(--txt);font-size:.875rem;font-weight:500">Usuario</label>
          <input type="text" name="usuario" placeholder="Ej. admin"
                 value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>" 
                 style="width:100%;padding:11px 14px;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.18);color:#f0f4f8;font-size:.9rem;border-radius:8px"
                 required>
        </div>
        <div class="form-group">
          <label style="display:block;margin-bottom:6px;color:var(--txt);font-size:.875rem;font-weight:500">Nueva contraseña</label>
          <input type="password" name="nueva_password" placeholder="Mínimo 6 caracteres"
                 style="width:100%;padding:11px 14px;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.18);color:#f0f4f8;font-size:.9rem;border-radius:8px"
                 required>
        </div>
        <div class="form-group">
          <label style="display:block;margin-bottom:6px;color:var(--txt);font-size:.875rem;font-weight:500">Confirmar contraseña</label>
          <input type="password" name="confirmar" placeholder="Repite la contraseña"
                 style="width:100%;padding:11px 14px;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.18);color:#f0f4f8;font-size:.9rem;border-radius:8px"
                 required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:4px">
          <i class="bi bi-check-circle"></i> Guardar contraseña
        </button>
      </form>
    </div>

    <!-- Lista de usuarios -->
    <?php if (!empty($usuarios)): ?>
    <div class="section-card">
      <div class="section-head"><span class="section-title">Usuarios existentes</span></div>
      <div class="tbl-wrap">
        <table style="width:100%">
          <thead>
            <tr><th>ID</th><th>Usuario</th></tr>
          </thead>
          <tbody>
            <?php foreach ($usuarios as $u): ?>
            <tr>
              <td style="color:var(--txt-muted);font-size:.85rem">#<?= $u['id'] ?></td>
              <td style="color:var(--txt);font-weight:500"><?= htmlspecialchars($u['usuario']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <div class="alert" style="margin-top:20px;background:rgba(205,92,92,0.1);border-color:#cd5c5c44;color:#cd5c5c">
    <i class="bi bi-exclamation-triangle"></i> <strong>IMPORTANTE:</strong> Elimina este archivo (setup_password.php) después de usarlo por razones de seguridad.
  </div>

</main>
</body>
</html>