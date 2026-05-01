<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'freshstock_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHAR', 'utf8mb4');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHAR,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // En producción, loguear el error en lugar de mostrarlo
    error_log('DB Error: ' . $e->getMessage());
    die('<div style="color:#ef4444;font-family:sans-serif;padding:20px">
         <i class="bi bi-exclamation-triangle"></i> Error de conexión a la base de datos. Contacte al administrador.</div>');
}

/**
 * Calcula el estado de un producto basado en stock y fecha de vencimiento.
 * Retorna: 'vencido' | 'por_vencer' | 'bajo_stock' | 'ok'
 */
function estadoProducto(int $stock, int $stockMin, string $fechaVenc): string {
    $hoy     = new DateTime();
    $venc    = new DateTime($fechaVenc);
    $diff    = (int)$hoy->diff($venc)->format('%r%a'); // negativo si ya venció

    if ($diff < 0)   return 'vencido';
    if ($diff <= 30)  return 'por_vencer';
    if ($stock <= $stockMin) return 'bajo_stock';
    return 'ok';
}

/**
 * Retorna el HTML del badge de estado.
 */
function badgeEstado(string $estado): string {
    return match($estado) {
        'vencido'    => '<span class="badge badge-danger"><i class="bi bi-exclamation-triangle"></i> Vencido</span>',
        'por_vencer' => '<span class="badge badge-warn"><i class="bi bi-exclamation-octagon"></i> Por vencer</span>',
        'bajo_stock' => '<span class="badge badge-low"><i class="bi bi-graph-down"></i> Bajo stock</span>',
        default      => '<span class="badge badge-ok"><i class="bi bi-check-circle"></i> OK</span>',
    };
}

/**
 * Formatea fecha a dd/mm/yyyy
 */
function fmtFecha(string $fecha): string {
    return date('d/m/Y', strtotime($fecha));
}
