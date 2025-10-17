<?php
session_start();
require 'database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$clienteId = $data['cliente_id'] ?? 0;

if ($clienteId) {
    // Actualizar la última asistencia del cliente como reportada
    $stmt = $pdo->prepare("UPDATE asistencias SET reportada = 1 
                          WHERE cliente_id = ? 
                          ORDER BY fecha_hora DESC 
                          LIMIT 1");
    $stmt->execute([$clienteId]);
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}