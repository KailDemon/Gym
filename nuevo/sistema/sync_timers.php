<?php
session_start();
header('Content-Type: application/json');

$response = [];

if (isset($_SESSION['monitoreo'])) {
    foreach ($_SESSION['monitoreo'] as $id => $cliente) {
        // Calcular tiempo transcurrido real
        $tiempoTranscurrido = 0;
        
        if ($cliente['estado'] === 'activo') {
            $tiempoTranscurrido = time() - $cliente['start_time'] - $cliente['paused_time'];
        } else {
            $tiempoTranscurrido = $cliente['paused_time'];
        }
        
        // Limitar el tiempo transcurrido al tiempo máximo
        $tiempoTranscurrido = min($tiempoTranscurrido, $cliente['tiempo_maximo']);
        
        $response[$id] = [
            'start_time' => $cliente['start_time'],
            'paused_time' => $cliente['paused_time'],
            'estado' => $cliente['estado'],
            'tiempo_transcurrido' => $tiempoTranscurrido
        ];
    }
}

echo json_encode($response);