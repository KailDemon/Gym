<?php
session_start();
require 'database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$cliente_id = $_GET['id'] ?? 0;

// Obtener información del cliente
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$cliente_id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    $_SESSION['mensaje'] = "Cliente no encontrado";
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: asistencia.php');
    exit;
}

// Obtener historial de asistencias
$stmt = $pdo->prepare("SELECT a.fecha_hora, u.nombre AS usuario 
                      FROM asistencias a
                      JOIN usuarios u ON a.usuario_id = u.id
                      WHERE a.cliente_id = ?
                      ORDER BY a.fecha_hora DESC");
$stmt->execute([$cliente_id]);
$asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas
$totalAsistencias = count($asistencias);
$primeraAsistencia = $totalAsistencias ? $asistencias[$totalAsistencias - 1]['fecha_hora'] : 'N/A';
$ultimaAsistencia = $totalAsistencias ? $asistencias[0]['fecha_hora'] : 'N/A';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Asistencias - <?php echo $cliente['nombre']; ?></title>
    <link rel="stylesheet" href="css/diseño.css">
    <style>
        .print-only { display: none; }
        .stats-card { background: #f8f9fa; border-radius: 5px; padding: 15px; margin-bottom: 20px; }
        .stats-card h4 { margin-top: 0; }
        @media print {
            .no-print { display: none; }
            .print-only { display: block; }
            body { background: white; }
            .container { width: 100%; padding: 0; }
            .sidebar, .header, .btn { display: none; }
            .main-content { margin-left: 0; }
            table { width: 100%; font-size: 12px; }
            .stats-container { display: flex; justify-content: space-between; }
            .stats-card { width: 30%; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar no-print">
            <div class="sidebar-header">
                <h2>Gimnasio </h2>
            </div>
            <div class="sidebar-menu">
                <ul>
                    <li><a href="index.php"><i>🏠</i> Inicio</a></li>
                    <li><a href="clientes.php"><i>👥</i> Clientes</a></li>
                    <li><a href="usuarios.php"><i>👤</i> Usuarios</a></li>
                    <li><a href="asistencia.php"><i>✅</i> Asistencia</a></li>
                    <li><a href="logout.php"><i>🔒</i> Cerrar Sesión</a></li>
                </ul>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header no-print">
                <h1>Historial de Asistencias</h1>
                <div class="user-info">
                    <span>Bienvenido, <?php echo $_SESSION['usuario_nombre']; ?></span>
                    <a href="logout.php" class="btn btn-danger">Cerrar Sesión</a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>
                        <?php echo $cliente['nombre'] . ' ' . $cliente['apellido']; ?>
                        <span class="no-print">
                            <button onclick="window.print()" class="btn btn-secondary">Imprimir Historial</button>
                            <a href="asistencia.php" class="btn btn-primary">Volver</a>
                        </span>
                    </h3>
                </div>
                <div class="card-body">
                    <div class="print-only">
                        <h2>Historial de Asistencias</h2>
                        <h3><?php echo $cliente['nombre'] . ' ' . $cliente['apellido']; ?></h3>
                        <p>Gimnasio  - <?php echo date('d/m/Y H:i'); ?></p>
                        <p>Generado por: <?php echo $_SESSION['usuario_nombre']; ?></p>
                    </div>
                    
                    <div class="stats-container">
                        <div class="stats-card">
                            <h4>Total de Asistencias</h4>
                            <p class="stat-value"><?php echo $totalAsistencias; ?></p>
                        </div>
                        <div class="stats-card">
                            <h4>Primera Asistencia</h4>
                            <p class="stat-value">
                                <?php echo $primeraAsistencia != 'N/A' ? date('d/m/Y', strtotime($primeraAsistencia)) : 'N/A'; ?>
                            </p>
                        </div>
                        <div class="stats-card">
                            <h4>Última Asistencia</h4>
                            <p class="stat-value">
                                <?php echo $ultimaAsistencia != 'N/A' ? date('d/m/Y H:i', strtotime($ultimaAsistencia)) : 'N/A'; ?>
                            </p>
                        </div>
                    </div>
                    
                    <?php if (!empty($asistencias)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Fecha y Hora</th>
                                <th>Registrado por</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($asistencias as $asistencia): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($asistencia['fecha_hora'])); ?></td>
                                <td><?php echo $asistencia['usuario']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p>Este cliente no tiene asistencias registradas.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>