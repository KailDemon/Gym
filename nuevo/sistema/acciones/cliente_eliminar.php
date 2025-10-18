<?php
session_start();
require '../database.php';

if (!isset($_SESSION['usuario_id']) || !isset($_GET['cedula'])) {
    header('Location: ../login.php');
    exit;
}

$cedula = $_GET['cedula'];

try {
    // Eliminar de la tabla clientes (se eliminará en cascada de usuarios por la relación)
    $stmt = $pdo->prepare("DELETE FROM clientes WHERE cedula = ?");
    $result = $stmt->execute([$cedula]);
    
    if ($result) {
        $_SESSION['mensaje'] = 'Cliente eliminado correctamente';
        $_SESSION['tipo_mensaje'] = 'success';
    } else {
        $_SESSION['mensaje'] = 'Error al eliminar el cliente';
        $_SESSION['tipo_mensaje'] = 'danger';
    }
} catch (PDOException $e) {
    $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
    $_SESSION['tipo_mensaje'] = 'danger';
}

header('Location: ../clientes.php');
exit;
?>