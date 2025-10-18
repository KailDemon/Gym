<?php
session_start();
require '../database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if (!isset($_GET['cedula']) || empty($_GET['cedula'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Cédula no proporcionada']);
    exit;
}

$cedula = $_GET['cedula'];

try {
    // Obtener datos del usuario
    $stmt = $pdo->prepare("SELECT cedula, nombre, apellido, email, fecha_nacimiento, estado FROM usuarios WHERE cedula = ? AND rol = 'cliente'");
    $stmt->execute([$cedula]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario) {
        echo json_encode([
            'success' => true,
            'usuario' => $usuario
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Usuario no encontrado'
        ]);
    }
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?>