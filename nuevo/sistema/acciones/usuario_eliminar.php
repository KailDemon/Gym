<?php
session_start();
require '../database.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin' || !isset($_GET['cedula'])) {
    header('Location: ../login.php');
    exit;
}

$cedula = $_GET['cedula'];

// No permitir eliminarse a sí mismo
if ($cedula == $_SESSION['usuario_id']) {
    $_SESSION['mensaje'] = 'No puedes eliminar tu propio usuario';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: ../usuarios.php');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE cedula = ?");
    $result = $stmt->execute([$cedula]);

    if ($result && $stmt->rowCount() > 0) {
        $_SESSION['mensaje'] = 'Usuario eliminado correctamente';
        $_SESSION['tipo_mensaje'] = 'success';
    } else {
        $_SESSION['mensaje'] = 'Error al eliminar el usuario o usuario no encontrado';
        $_SESSION['tipo_mensaje'] = 'danger';
    }
} catch (PDOException $e) {
    // Verificar si es error de clave foránea
    if ($e->getCode() == '23000') {
        $_SESSION['mensaje'] = 'No se puede eliminar el usuario porque tiene registros relacionados en otras tablas';
    } else {
        $_SESSION['mensaje'] = 'Error en la base de datos: ' . $e->getMessage();
    }
    $_SESSION['tipo_mensaje'] = 'danger';
}

header('Location: ../usuarios.php');
exit;
?>