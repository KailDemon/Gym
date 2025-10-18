<?php
session_start();
require '../database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$codigo_qr = $data['codigo_qr'] ?? '';

if (empty($codigo_qr)) {
    echo json_encode(['success' => false, 'message' => 'Código QR no proporcionado']);
    exit;
}

// Buscar el cliente por el código QR
$stmt = $pdo->prepare("SELECT id, estado FROM clientes WHERE codigo_qr = ?");
$stmt->execute([$codigo_qr]);
$cliente = $stmt->fetch();

if (!$cliente) {
    echo json_encode(['success' => false, 'message' => 'Cliente no encontrado']);
    exit;
}

if ($cliente['estado'] != 'activo') {
    echo json_encode([
        'success' => false, 
        'message' => 'Membresía inactiva o suspendida'
    ]);
    exit;
}

// Registrar la asistencia
$stmt = $pdo->prepare("INSERT INTO asistencias (cliente_id) VALUES (?)");
if ($stmt->execute([$cliente['id']])) {
    echo json_encode([
        'success' => true, 
        'message' => 'Asistencia registrada correctamente'
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Error al registrar la asistencia'
    ]);
}