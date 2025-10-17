<?php
session_start();
require '../database.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin' || !isset($_GET['id'])) {
    header('Location: ../login.php');
    exit;
}

$id = (int)$_GET['id'];

// No permitir eliminarse a sí mismo
if ($id == $_SESSION['usuario_id']) {
    $_SESSION['mensaje'] = 'No puedes eliminar tu propio usuario';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: ../usuarios.php');
    exit;
}

$stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
$result = $stmt->execute([$id]);

if ($result) {
    $_SESSION['mensaje'] = 'Usuario eliminado correctamente';
    $_SESSION['tipo_mensaje'] = 'success';
} else {
    $_SESSION['mensaje'] = 'Error al eliminar el usuario';
    $_SESSION['tipo_mensaje'] = 'danger';
}

header('Location: ../usuarios.php');
exit;
?>