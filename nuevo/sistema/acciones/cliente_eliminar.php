<?php
session_start();
require '../database.php';

if (!isset($_SESSION['usuario_id']) || !isset($_GET['id'])) {
    header('Location: ../login.php');
    exit;
}

$id = (int)$_GET['id'];

$stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
$result = $stmt->execute([$id]);

if ($result) {
    $_SESSION['mensaje'] = 'Cliente eliminado correctamente';
    $_SESSION['tipo_mensaje'] = 'success';
} else {
    $_SESSION['mensaje'] = 'Error al eliminar el cliente';
    $_SESSION['tipo_mensaje'] = 'danger';
}

header('Location: ../clientes.php');
exit;
?>