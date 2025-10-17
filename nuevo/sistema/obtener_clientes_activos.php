<?php
session_start();
require 'database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

// Consulta para obtener clientes activos (última hora)
$stmt = $pdo->prepare("SELECT a.id, a.fecha_hora, c.id AS cliente_id, c.nombre, c.apellido 
                      FROM asistencias a
                      JOIN clientes c ON a.cliente_id = c.id
                      WHERE a.fecha_hora > NOW() - INTERVAL 1 HOUR
                      ORDER BY a.fecha_hora DESC");
$stmt->execute();
$clientesActivos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Formatear los datos para JSON
$resultados = [];
foreach ($clientesActivos as $cliente) {
    $resultados[] = [
        'id' => $cliente['cliente_id'],
        'nombre' => $cliente['nombre'],
        'apellido' => $cliente['apellido'],
        'fecha_hora' => $cliente['fecha_hora']
    ];
}

header('Content-Type: application/json');
echo json_encode($resultados);