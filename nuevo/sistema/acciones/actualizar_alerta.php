<?php
session_start();
require '../database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit;
}

$cliente_id = $_POST['cliente_id'] ?? 0;
$alerta = $_POST['alerta'] ?? 0;

if ($cliente_id > 0) {
    $stmt = $pdo->prepare("UPDATE clientes SET alerta_vencimiento = ? WHERE id = ?");
    if ($stmt->execute([$alerta ? 1 : 0, $cliente_id])) {
        $_SESSION['mensaje'] = "Configuración de alerta actualizada";
        $_SESSION['tipo_mensaje'] = 'success';
    } else {
        $_SESSION['mensaje'] = "Error al actualizar la alerta";
        $_SESSION['tipo_mensaje'] = 'danger';
    }
}

header("Location: ../clientes.php");
exit;