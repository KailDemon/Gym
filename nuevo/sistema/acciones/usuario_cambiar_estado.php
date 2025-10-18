<?php
session_start();
require '../database.php';

// Verificar permisos - solo administradores pueden cambiar estados
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin' || !isset($_GET['cedula'])) {
    header('Location: ../login.php');
    exit;
}

$cedula = $_GET['cedula'];

// No permitir cambiarse a sí mismo
if ($cedula == $_SESSION['usuario_id']) {
    $_SESSION['mensaje'] = 'No puedes cambiar tu propio estado';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: ../usuarios.php');
    exit;
}

try {
    // Obtener el estado actual del usuario
    $stmt = $pdo->prepare("SELECT estado FROM usuarios WHERE cedula = ?");
    $stmt->execute([$cedula]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        $_SESSION['mensaje'] = 'Usuario no encontrado';
        $_SESSION['tipo_mensaje'] = 'danger';
        header('Location: ../usuarios.php');
        exit;
    }

    // Determinar el nuevo estado (rotación: activo -> pendiente -> inactivo -> activo)
    $estado_actual = $usuario['estado'];
    $nuevo_estado = '';
    
    switch ($estado_actual) {
        case 'activo':
            $nuevo_estado = 'inactivo';
            break;
        case 'pendiente':
            $nuevo_estado = 'activo';
            break;
        case 'inactivo':
            $nuevo_estado = 'activo';
            break;
        default:
            $nuevo_estado = 'activo';
    }

    // Actualizar el estado
    $stmt = $pdo->prepare("UPDATE usuarios SET estado = ? WHERE cedula = ?");
    $result = $stmt->execute([$nuevo_estado, $cedula]);

    if ($result) {
        $_SESSION['mensaje'] = "Estado del usuario cambiado a " . ucfirst($nuevo_estado) . " correctamente";
        $_SESSION['tipo_mensaje'] = 'success';
    } else {
        $_SESSION['mensaje'] = 'Error al cambiar el estado del usuario';
        $_SESSION['tipo_mensaje'] = 'danger';
    }
} catch (PDOException $e) {
    $_SESSION['mensaje'] = 'Error en la base de datos: ' . $e->getMessage();
    $_SESSION['tipo_mensaje'] = 'danger';
}

header('Location: ../usuarios.php');
exit;
?>