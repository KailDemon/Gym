<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cabeceras para evitar caché
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Validar tiempo de inactividad (30 minutos)
$inactividad = 1800;
if (isset($_SESSION['ultimo_acceso'])) {
    $tiempo_inactivo = time() - $_SESSION['ultimo_acceso'];
    if ($tiempo_inactivo > $inactividad) {
        session_unset();
        session_destroy();
        header('Location: login.php?timeout=1');
        exit;
    }
}
$_SESSION['ultimo_acceso'] = time();
?>