<?php
session_start();
require 'database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Obtener la cédula del cliente desde el parámetro
$cliente_cedula = $_GET['cedula'] ?? '';

if (empty($cliente_cedula)) {
    $_SESSION['mensaje'] = "Cliente no especificado";
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: asistencia.php');
    exit;
}

// Obtener información del cliente
$stmt = $pdo->prepare("SELECT c.cedula, u.nombre, c.apellido, u.email, c.telefono, c.tipo_membresia, c.fecha_inicio, c.fecha_vencimiento
                      FROM clientes c 
                      INNER JOIN usuarios u ON c.cedula = u.cedula 
                      WHERE c.cedula = ?");
$stmt->execute([$cliente_cedula]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    $_SESSION['mensaje'] = "Cliente no encontrado";
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: asistencia.php');
    exit;
}

// Obtener historial de asistencias con información de actividades
$stmt = $pdo->prepare("SELECT a.fecha_hora, ac.nombre AS actividad, u.nombre AS usuario 
                      FROM asistencias a
                      JOIN actividades ac ON a.actividad_id = ac.id
                      JOIN usuarios u ON a.usuario_id = u.cedula
                      WHERE a.cliente_id = ?
                      ORDER BY a.fecha_hora DESC");
$stmt->execute([$cliente_cedula]);
$asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener actividades inscritas por el cliente
$stmtActividades = $pdo->prepare("SELECT ac.nombre 
                                 FROM cliente_actividad ca
                                 JOIN actividades ac ON ca.actividad_id = ac.id
                                 WHERE ca.cliente_id = ?");
$stmtActividades->execute([$cliente_cedula]);
$actividades_inscritas = $stmtActividades->fetchAll(PDO::FETCH_COLUMN);

// Estadísticas
$totalAsistencias = count($asistencias);
$primeraAsistencia = $totalAsistencias ? $asistencias[$totalAsistencias - 1]['fecha_hora'] : 'N/A';
$ultimaAsistencia = $totalAsistencias ? $asistencias[0]['fecha_hora'] : 'N/A';

// Calcular asistencias por actividad
$asistenciasPorActividad = [];
foreach ($asistencias as $asistencia) {
    $actividad = $asistencia['actividad'];
    if (!isset($asistenciasPorActividad[$actividad])) {
        $asistenciasPorActividad[$actividad] = 0;
    }
    $asistenciasPorActividad[$actividad]++;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Asistencias - <?php echo htmlspecialchars($cliente['nombre']); ?></title>
    <link rel="stylesheet" href="css/diseño.css">
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2c3e50;
            --success: #27ae60;
            --danger: #e74c3c;
            --warning: #f39c12;
            --light: #ecf0f1;
            --dark: #34495e;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
        }
        
        .container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background-color: var(--secondary);
            color: white;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            position: fixed;
            height: 100%;
        }
        
        .sidebar-header {
            padding: 20px;
            background-color: rgba(0,0,0,0.2);
            text-align: center;
        }
        
        .sidebar-menu ul {
            list-style: none;
            padding: 20px 0;
        }
        
        .sidebar-menu li a {
            display: block;
            padding: 15px 20px;
            color: #ecf0f1;
            text-decoration: none;
            font-size: 16px;
        }
        
        .sidebar-menu li a i {
            margin-right: 10px;
            font-size: 20px;
            width: 24px;
            display: inline-block;
            text-align: center;
        }
        
        .sidebar-menu li a:hover, .sidebar-menu li a.active {
            background-color: rgba(255,255,255,0.1);
            border-left: 4px solid var(--primary);
            padding-left: 16px;
        }
        
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .btn-primary { background-color: var(--primary); color: white; }
        .btn-secondary { background-color: #95a5a6; color: white; }
        .btn-success { background-color: var(--success); color: white; }
        
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .card-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8f9fa;
        }
        
        .card-body {
            padding: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        table th, table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--secondary);
        }
        
        .print-only { display: none; }
        
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
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stats-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            border-left: 4px solid var(--primary);
        }
        
        .stats-card h4 {
            margin-top: 0;
            color: var(--secondary);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary);
            margin: 10px 0;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .info-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #eee;
        }
        
        .info-card h4 {
            margin-top: 0;
            color: var(--secondary);
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .info-label {
            font-weight: 500;
            color: #555;
        }
        
        .info-value {
            color: var(--secondary);
            font-weight: 600;
        }
        
        .activity-tag {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
            margin-right: 5px;
            margin-bottom: 5px;
            color: white;
        }
        
        .tag-gimnasio { background-color: #3498db; }
        .tag-boxeo { background-color: #e74c3c; }
        .tag-taekwondo { background-color: #27ae60; }
        .tag-spinning { background-color: #f39c12; }
        
        .membership-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .badge-basica { background-color: #95a5a6; color: white; }
        .badge-premium { background-color: #f39c12; color: white; }
        .badge-vip { background-color: #e74c3c; color: white; }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            .sidebar-header h2, .sidebar-menu li a span {
                display: none;
            }
            .main-content {
                margin-left: 70px;
            }
            .stats-container {
                grid-template-columns: 1fr;
            }
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar no-print">
            <div class="sidebar-header">
                <h2>Gimnasio</h2>
            </div>
            <div class="sidebar-menu">
                <ul>
                    <li><a href="index.php"><i>🏠</i> <span>Inicio</span></a></li>
                    <li><a href="clientes.php"><i>👥</i> <span>Clientes</span></a></li>
                    <li><a href="asistencia.php" class="active"><i>✅</i> <span>Asistencia</span></a></li>
                    <li><a href="monitoreo.php"><i>⏱️</i> <span>Monitoreo</span></a></li>
                    <li><a href="usuarios.php"><i>👤</i> <span>Usuarios</span></a></li>
                    <li><a href="escanner.php"><i>📷</i> <span>Escanear QR</span></a></li>
                    <li><a href="logout.php"><i>🔒</i> <span>Cerrar Sesión</span></a></li>
                </ul>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header no-print">
                <h1>Historial de Asistencias</h1>
                <div class="user-info">
                    <span>Bienvenido, <?php echo $_SESSION['usuario_nombre']; ?></span>
                    <a href="logout.php" class="btn btn-primary">Cerrar Sesión</a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>
                        <?php echo htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido']); ?>
                        <span class="no-print">
                            <button onclick="window.print()" class="btn btn-secondary">Imprimir Historial</button>
                            <a href="asistencia.php" class="btn btn-success">Volver a Asistencia</a>
                        </span>
                    </h3>
                </div>
                <div class="card-body">
                    <div class="print-only">
                        <h2>Historial de Asistencias</h2>
                        <h3><?php echo htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido']); ?></h3>
                        <p>Gimnasio - <?php echo date('d/m/Y H:i'); ?></p>
                        <p>Generado por: <?php echo $_SESSION['usuario_nombre']; ?></p>
                    </div>
                    
                    <!-- Información del Cliente -->
                    <div class="info-grid">
                        <div class="info-card">
                            <h4>Información Personal</h4>
                            <div class="info-item">
                                <span class="info-label">Cédula:</span>
                                <span class="info-value"><?php echo htmlspecialchars($cliente['cedula']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Email:</span>
                                <span class="info-value"><?php echo htmlspecialchars($cliente['email']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Teléfono:</span>
                                <span class="info-value"><?php echo htmlspecialchars($cliente['telefono']); ?></span>
                            </div>
                        </div>
                        
                        <div class="info-card">
                            <h4>Membresía</h4>
                            <div class="info-item">
                                <span class="info-label">Tipo:</span>
                                <span class="info-value">
                                    <span class="membership-badge badge-<?php echo strtolower($cliente['tipo_membresia']); ?>">
                                        <?php echo htmlspecialchars($cliente['tipo_membresia']); ?>
                                    </span>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Inicio:</span>
                                <span class="info-value"><?php echo date('d/m/Y', strtotime($cliente['fecha_inicio'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Vencimiento:</span>
                                <span class="info-value"><?php echo date('d/m/Y', strtotime($cliente['fecha_vencimiento'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="info-card">
                            <h4>Actividades Inscritas</h4>
                            <?php if (!empty($actividades_inscritas)): ?>
                                <?php foreach ($actividades_inscritas as $actividad): 
                                    $actividadClass = 'tag-' . strtolower($actividad);
                                ?>
                                    <span class="activity-tag <?php echo $actividadClass; ?>">
                                        <?php echo htmlspecialchars($actividad); ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>No inscrito en actividades</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Estadísticas -->
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
                    
                    <!-- Asistencias por actividad -->
                    <?php if (!empty($asistenciasPorActividad)): ?>
                    <div class="info-card">
                        <h4>Asistencias por Actividad</h4>
                        <?php foreach ($asistenciasPorActividad as $actividad => $count): 
                            $actividadClass = 'tag-' . strtolower($actividad);
                        ?>
                            <div class="info-item">
                                <span class="info-label">
                                    <span class="activity-tag <?php echo $actividadClass; ?>">
                                        <?php echo htmlspecialchars($actividad); ?>
                                    </span>
                                </span>
                                <span class="info-value"><?php echo $count; ?> asistencias</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Historial detallado -->
                    <h3>Historial Detallado de Asistencias</h3>
                    <?php if (!empty($asistencias)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Fecha y Hora</th>
                                <th>Actividad</th>
                                <th>Registrado por</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($asistencias as $asistencia): 
                                $actividadClass = 'tag-' . strtolower($asistencia['actividad']);
                            ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($asistencia['fecha_hora'])); ?></td>
                                <td>
                                    <span class="activity-tag <?php echo $actividadClass; ?>">
                                        <?php echo htmlspecialchars($asistencia['actividad']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($asistencia['usuario']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="info-card">
                        <p>Este cliente no tiene asistencias registradas.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>