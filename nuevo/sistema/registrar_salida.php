<?php
session_start();
require 'database.php';

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar que se haya proporcionado un ID
if (!isset($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'ID de cliente no proporcionado']);
    exit;
}

$clienteId = $_GET['id'];
$usuarioId = $_SESSION['usuario_id'];

try {
    // Registrar la salida en la base de datos
    $stmt = $pdo->prepare("UPDATE asistencia SET fecha_hora_salida = NOW() WHERE cliente_id = ? AND fecha_hora_salida IS NULL");
    $stmt->execute([$clienteId]);

    // Verificar si se actualizó alguna fila
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontró asistencia activa para este cliente']);
    }
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>