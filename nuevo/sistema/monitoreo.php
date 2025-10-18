<?php
session_start();
require 'database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Obtener clientes activos de la base de datos
$stmt = $pdo->query("SELECT c.cedula, u.nombre, c.apellido 
                      FROM clientes c 
                      INNER JOIN usuarios u ON c.cedula = u.cedula 
                      WHERE u.estado = 'activo'");
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['agregar_cliente'])) {
        // Validar datos
        if (empty($_POST['cliente_id']) && empty($_POST['custom_name'])) {
            $_SESSION['mensaje'] = 'Por favor, seleccione un cliente o ingrese un nombre personalizado';
            $_SESSION['tipo_mensaje'] = 'error';
        } else {
            // Inicializar $cliente como array vacío
            $cliente = [];
            
            // Crear nuevo cliente si es personalizado
            if (!empty($_POST['custom_name'])) {
                $cliente = [
                    'cedula' => 'custom_'.time(),
                    'nombre' => $_POST['custom_name'],
                    'apellido' => '',
                ];
            } else {
                // Obtener cliente de la base de datos - CORREGIDO: usar cedula
                $cedula = $_POST['cliente_id'];
                $cliente_encontrado = false;
                foreach ($clientes as $c) {
                    if ($c['cedula'] == $cedula) {
                        $cliente = $c;
                        $cliente_encontrado = true;
                        break;
                    }
                }
                
                if (!$cliente_encontrado) {
                    $_SESSION['mensaje'] = 'El cliente seleccionado no existe';
                    $_SESSION['tipo_mensaje'] = 'error';
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                }
            }
            
            // Verificar si ya está en monitoreo - CORREGIDO: usar cedula como clave
            if (isset($_SESSION['monitoreo'][$cliente['cedula']])) {
                $_SESSION['mensaje'] = 'Este cliente ya está siendo monitoreado';
                $_SESSION['tipo_mensaje'] = 'error';
            } else {
                // Calcular tiempo máximo
                $minutos = (int)$_POST['minutes'];
                $segundos = (int)$_POST['seconds'];
                $tiempo_maximo = ($minutos * 60) + $segundos;
                
                // Agregar a monitoreo con timestamp inicial - CORREGIDO: usar cedula como clave
                $_SESSION['monitoreo'][$cliente['cedula']] = [
                    'cedula' => $cliente['cedula'],
                    'nombre' => $cliente['nombre'],
                    'apellido' => $cliente['apellido'] ?? '',
                    'tiempo_maximo' => $tiempo_maximo,
                    'estado' => 'activo',
                    'start_time' => time(),
                    'paused_time' => 0
                ];
                
                $_SESSION['mensaje'] = 'Cliente agregado al monitoreo';
                $_SESSION['tipo_mensaje'] = 'success';
            }
        }
    } 
    // Procesar acciones sobre clientes monitoreados
    elseif (isset($_POST['accion'])) {
        $cliente_id = $_POST['cliente_id'];
        
        if (isset($_SESSION['monitoreo'][$cliente_id])) {
            switch ($_POST['accion']) {
                case 'pausar':
                    // Calcular tiempo transcurrido y actualizar estado
                    $elapsed = time() - $_SESSION['monitoreo'][$cliente_id]['start_time'] - $_SESSION['monitoreo'][$cliente_id]['paused_time'];
                    $_SESSION['monitoreo'][$cliente_id]['paused_time'] = $elapsed;
                    $_SESSION['monitoreo'][$cliente_id]['estado'] = 'pausado';
                    break;
                    
                case 'reanudar':
                    // Actualizar timestamp de inicio
                    $_SESSION['monitoreo'][$cliente_id]['start_time'] = time();
                    $_SESSION['monitoreo'][$cliente_id]['estado'] = 'activo';
                    break;
                    
                case 'eliminar':
                    unset($_SESSION['monitoreo'][$cliente_id]);
                    break;
            }
        }
    }
    
    // Redirigir para evitar reenvío de formulario
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Obtener mensajes
$mensaje = $_SESSION['mensaje'] ?? '';
$tipoMensaje = $_SESSION['tipo_mensaje'] ?? '';
unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);

// Inicializar monitoreo si no existe
if (!isset($_SESSION['monitoreo'])) {
    $_SESSION['monitoreo'] = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoreo Manual - Gimnasio Fit</title>
    <link rel="stylesheet" href="css/diseño.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Estilos mejorados y optimizados */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
            padding: 0;
        }
        
        .container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            height: 100vh;
            position: fixed;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            z-index: 100;
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        
        .sidebar-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .sidebar-menu ul {
            list-style: none;
        }
        
        .sidebar-menu li a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
            font-size: 1rem;
        }
        
        .sidebar-menu li a i {
            margin-right: 12px;
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }
        
        .sidebar-menu li a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            padding-left: 25px;
        }
        
        .sidebar-menu li a.active {
            background: rgba(255,255,255,0.15);
            color: white;
            border-left: 4px solid #4CAF50;
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
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e0e6ed;
        }
        
        .header h1 {
            font-size: 1.8rem;
            color: #2c3e50;
            font-weight: 600;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-info span {
            font-size: 1rem;
            color: #555;
        }
        
        .btn {
            padding: 8px 15px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
        }
        
        .btn-secondary {
            background-color: #3498db;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #2980b9;
        }
        
        .btn-success {
            background-color: #2ecc71;
            color: white;
        }
        
        .btn-success:hover {
            background-color: #27ae60;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .card-header {
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            background: #f8f9fa;
            border-bottom: 1px solid #eaeff5;
        }
        
        .card-header h3 {
            font-size: 1.4rem;
            color: #2c3e50;
            font-weight: 600;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .monitor-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            padding: 10px;
        }
        
        .cliente-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            padding: 25px;
            position: relative;
            transition: all 0.3s;
            border: 1px solid #eaeff5;
            overflow: hidden;
        }
        
        .cliente-card.warning {
            background: #fff8e1;
            border-left: 4px solid #ffc107;
            animation: pulse 1.5s infinite;
        }
        
        .cliente-card.expired {
            background: #ffebee;
            border-left: 4px solid #f44336;
        }
        
        .cliente-card.paused {
            background: #f5f5f5;
            border-left: 4px solid #9e9e9e;
        }
        
        .cliente-card.normal {
            border-left: 4px solid #4CAF50;
        }
        
        .cliente-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .cliente-info {
            flex: 1;
        }
        
        .cliente-info strong {
            display: block;
            font-size: 1.1rem;
            color: #2c3e50;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .hora-entrada {
            font-size: 0.85rem;
            color: #7f8c8d;
            font-weight: 500;
        }
        
        .cliente-status {
            background: #e0f7fa;
            color: #00838f;
            font-size: 0.8rem;
            padding: 3px 8px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .timer-container {
            text-align: center;
            margin: 20px 0;
            position: relative;
        }
        
        .timer {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            font-family: 'Courier New', monospace;
            letter-spacing: 2px;
        }
        
        .timer-label {
            font-size: 0.9rem;
            color: #7f8c8d;
            margin-top: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .alert-bar-container {
            height: 8px;
            background: #f0f3f5;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 15px;
        }
        
        .alert-bar {
            height: 100%;
            border-radius: 4px;
            background: #4CAF50;
            transition: width 0.5s ease;
        }
        
        .actions {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 20px;
        }
        
        .action-btn {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            font-size: 1.1rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .action-btn:active {
            transform: translateY(0);
        }
        
        .play-btn {
            background-color: #4CAF50;
            color: white;
        }
        
        .pause-btn {
            background-color: #FFC107;
            color: white;
        }
        
        .exit-btn {
            background-color: #F44336;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #95a5a6;
            grid-column: 1 / -1;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #ecf0f1;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #7f8c8d;
        }
        
        .empty-state p {
            font-size: 1.1rem;
            max-width: 500px;
            margin: 0 auto;
            line-height: 1.6;
        }
        
        .notification {
            position: fixed;
            top: 25px;
            right: 25px;
            padding: 18px 30px;
            border-radius: 8px;
            background: #f44336;
            color: white;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 12px;
            transform: translateX(120%);
            transition: transform 0.4s ease;
        }
        
        .notification.show {
            transform: translateX(0);
        }
        
        .add-client-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #dce1e8;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .time-inputs {
            display: flex;
            gap: 10px;
        }
        
        .time-input {
            flex: 1;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.4); }
            70% { box-shadow: 0 0 0 12px rgba(255, 193, 7, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); }
        }
        
        .controls-info {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            margin-top: 10px;
        }
        
        .legend {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
            color: #555;
        }
        
        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 3px;
        }
        
        .normal-color {
            background: #4CAF50;
        }
        
        .paused-color {
            background: #9e9e9e;
        }
        
        .warning-color {
            background: #FFC107;
        }
        
        .expired-color {
            background: #F44336;
        }
        
        @media (max-width: 992px) {
            .monitor-container {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar-header h2 {
                font-size: 0;
            }
            
            .sidebar-header h2:after {
                content: "G";
                font-size: 1.5rem;
            }
            
            .sidebar-menu li a span {
                display: none;
            }
            
            .sidebar-menu li a i {
                margin-right: 0;
                font-size: 1.4rem;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .user-info {
                width: 100%;
                justify-content: space-between;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 576px) {
            .monitor-container {
                grid-template-columns: 1fr;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .controls-info {
                width: 100%;
                flex-direction: column;
                align-items: flex-start;
            }
            
            .time-inputs {
                flex-direction: column;
            }
        }
        
        /* Mensajes */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: 500;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Gimnasio </h2>
            </div>
            <div class="sidebar-menu">
                <ul>
                    <li><a href="index.php"><i>🏠</i> <span>Inicio</span></a></li>
                    <li><a href="clientes.php"><i>👥</i> <span>Clientes</span></a></li>
                    <li><a href="asistencia.php"><i>✅</i> <span>Asistencia</span></a></li>
                    <li><a href="usuarios.php"><i>👤</i> <span>Usuarios</span></a></li>
                    <li><a href="monitoreo.php" class="active"><i>⏱️</i> <span>Monitoreo</span></a></li>
                    <li><a href="escanner.php"><i>📷</i> <span>Escanear QR</span></a></li>
                    <li><a href="logout.php"><i>🔒</i> <span>Cerrar Sesión</span></a></li>
                </ul>
            </div>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>Monitoreo Manual de Clientes</h1>
                <div class="user-info">
                    <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></span>
                    <a href="logout.php" class="btn btn-danger">Cerrar Sesión</a>
                </div>
            </div>
            
            <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipoMensaje; ?>">
                <?php echo $mensaje; ?>
            </div>
            <?php endif; ?>
            
            <div class="add-client-form">
                <h2>Agregar Cliente al Monitor</h2>
                <form method="post">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="cliente_id">Seleccionar Cliente Activo</label>
                            <select id="cliente_id" name="cliente_id" class="form-control">
                                <option value="">-- Seleccione un cliente --</option>
                                <?php foreach ($clientes as $cliente): ?>
                                    <option value="<?php echo $cliente['cedula']; ?>">
                                        <?php echo htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido'] . ' (' . $cliente['cedula'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="custom-name">Nombre Personalizado (opcional)</label>
                            <input type="text" id="custom-name" name="custom_name" class="form-control" placeholder="O ingrese un nombre personalizado">
                        </div>
                        
                        <div class="form-group">
                            <label for="tiempo-monitoreo">Tiempo de Monitoreo</label>
                            <div class="time-inputs">
                                <div class="time-input">
                                    <input type="number" id="minutes" name="minutes" class="form-control" min="0" max="180" value="45" placeholder="Minutos">
                                </div>
                                <div class="time-input">
                                    <input type="number" id="seconds" name="seconds" class="form-control" min="0" max="59" value="0" placeholder="Segundos">
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="agregar_cliente" class="btn btn-success">
                        <i class="fas fa-plus"></i> Agregar al Monitor
                    </button>
                </form>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Clientes en Monitoreo</h3>
                    <div>
                        <div class="controls-info">
                            <div class="legend">
                                <div class="legend-item">
                                    <div class="legend-color normal-color"></div>
                                    <span>Normal</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color paused-color"></div>
                                    <span>Pausado</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color warning-color"></div>
                                    <span>Advertencia</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color expired-color"></div>
                                    <span>Tiempo Expirado</span>
                                </div>
                            </div>
                        </div>
                        <button id="toggle-sound" class="btn btn-secondary">🔈 Sonido: ON</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="monitor-container" id="monitor-container">
                        <?php if (empty($_SESSION['monitoreo'])): ?>
                        <div class="empty-state">
                            <i class="fas fa-user-clock"></i>
                            <h3>No hay clientes en monitoreo</h3>
                            <p>Agrega clientes manualmente para comenzar el monitoreo</p>
                        </div>
                        <?php else: ?>
                            <?php foreach ($_SESSION['monitoreo'] as $cliente): 
                                // Asegurar que todas las claves existan
                                $cliente = array_merge([
                                    'cedula' => '',
                                    'nombre' => 'Cliente',
                                    'apellido' => '',
                                    'tiempo_maximo' => 0,
                                    'estado' => 'activo',
                                    'start_time' => time(),
                                    'paused_time' => 0
                                ], $cliente);
                                
                                // Determinar clase CSS según estado
                                $clase_card = 'normal';
                                if ($cliente['estado'] == 'pausado') {
                                    $clase_card = 'paused';
                                }
                                
                                // Calcular tiempo transcurrido
                                $tiempo_transcurrido = 0;
                                if ($cliente['estado'] === 'activo') {
                                    $tiempo_transcurrido = time() - $cliente['start_time'] - $cliente['paused_time'];
                                } else {
                                    $tiempo_transcurrido = $cliente['paused_time'];
                                }
                                
                                $tiempo_restante = max(0, $cliente['tiempo_maximo'] - $tiempo_transcurrido);
                            ?>
                            <div class="cliente-card <?php echo $clase_card; ?>" 
                                 id="card-<?php echo $cliente['cedula']; ?>"
                                 data-id="<?php echo $cliente['cedula']; ?>"
                                 data-estado="<?php echo $cliente['estado']; ?>"
                                 data-tiempo-maximo="<?php echo $cliente['tiempo_maximo']; ?>"
                                 data-start-time="<?php echo $cliente['start_time']; ?>"
                                 data-paused-time="<?php echo $cliente['paused_time']; ?>">
                                <div class="cliente-header">
                                    <div class="cliente-info">
                                        <strong><?php 
                                            echo htmlspecialchars($cliente['nombre'] . ($cliente['apellido'] ? ' ' . $cliente['apellido'] : '')); 
                                        ?></strong>
                                        <?php if (!empty($cliente['cedula']) && !str_starts_with($cliente['cedula'], 'custom_')): ?>
                                        <div class="hora-entrada">Cédula: <?php echo htmlspecialchars($cliente['cedula']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="cliente-status"><?php echo strtoupper($cliente['estado']); ?></div>
                                </div>
                                
                                <div class="timer-container">
                                    <div class="timer" id="timer-<?php echo $cliente['cedula']; ?>">
                                        <?php 
                                            $mins = floor($tiempo_restante / 60);
                                            $secs = $tiempo_restante % 60;
                                            echo sprintf('%02d:%02d', $mins, $secs); 
                                        ?>
                                    </div>
                                    <div class="timer-label">Tiempo restante</div>
                                </div>
                                
                                <div class="alert-bar-container">
                                    <div class="alert-bar" id="progress-<?php echo $cliente['cedula']; ?>" 
                                         style="width: <?php echo min(100, ($tiempo_transcurrido / $cliente['tiempo_maximo']) * 100); ?>%"></div>
                                </div>
                                
                                <div class="actions">
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="cliente_id" value="<?php echo $cliente['cedula']; ?>">
                                        <?php if ($cliente['estado'] == 'activo'): ?>
                                            <button type="submit" name="accion" value="pausar" class="action-btn pause-btn" title="Pausar">
                                                <i class="fas fa-pause"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" name="accion" value="reanudar" class="action-btn play-btn" title="Reanudar">
                                                <i class="fas fa-play"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button type="submit" name="accion" value="eliminar" class="action-btn exit-btn" title="Eliminar del monitor">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="notification" id="timeout-alert">
        <i class="fas fa-exclamation-circle"></i> ¡Tiempo completado! El cliente debe salir
    </div>
    
    <audio id="alert-sound" src="notification.mp3" preload="auto"></audio>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const alertSound = document.getElementById('alert-sound');
        const toggleSoundBtn = document.getElementById('toggle-sound');
        const notification = document.getElementById('timeout-alert');
        
        // Estado del sonido
        let soundOn = true;
        let expiredClients = [];
        let timers = {};
        
        // Función para formatear tiempo (minutos y segundos)
        function formatTime(seconds) {
            if (isNaN(seconds) || seconds < 0) return '00:00';
            
            seconds = Math.floor(seconds);
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }
        
        // Función para iniciar temporizador para un cliente
        function iniciarTemporizador(clienteId) {
            const card = document.getElementById(`card-${clienteId}`);
            if (!card) return;
            
            const timerEl = document.getElementById(`timer-${clienteId}`);
            const progressEl = document.getElementById(`progress-${clienteId}`);
            
            if (!timerEl || !progressEl) return;
            
            const estado = card.dataset.estado;
            const tiempoMaximo = parseInt(card.dataset.tiempoMaximo) || 0;
            const startTime = parseInt(card.dataset.startTime) || 0;
            const pausedTime = parseInt(card.dataset.pausedTime) || 0;
            let tiempoTranscurrido = 0;
            
            if (estado === 'activo') {
                tiempoTranscurrido = Math.floor((Date.now() / 1000) - startTime - pausedTime);
            } else {
                tiempoTranscurrido = pausedTime;
            }
            
            // Limitar el tiempo transcurrido al tiempo máximo
            tiempoTranscurrido = Math.min(tiempoTranscurrido, tiempoMaximo);
            
            // Solo actualizar si está activo
            if (estado === 'activo') {
                if (timers[clienteId]) {
                    clearInterval(timers[clienteId]);
                }
                
                // Si ya se alcanzó el tiempo máximo, no iniciar el intervalo
                if (tiempoTranscurrido >= tiempoMaximo) {
                    timerEl.textContent = '00:00';
                    progressEl.style.width = '100%';
                    card.classList.remove('normal', 'warning', 'paused');
                    card.classList.add('expired');
                    progressEl.style.background = '#F44336';
                    return;
                }
                
                timers[clienteId] = setInterval(() => {
                    // Si ya alcanzó el tiempo máximo, detener el temporizador
                    if (tiempoTranscurrido >= tiempoMaximo) {
                        clearInterval(timers[clienteId]);
                        return;
                    }
                    
                    tiempoTranscurrido++;
                    
                    const tiempoRestante = Math.max(0, tiempoMaximo - tiempoTranscurrido);
                    
                    // Actualizar temporizador
                    timerEl.textContent = formatTime(tiempoRestante);
                    
                    // Actualizar barra de progreso
                    const progressWidth = tiempoMaximo > 0 ? 
                        (tiempoTranscurrido / tiempoMaximo) * 100 : 0;
                    progressEl.style.width = `${Math.min(100, progressWidth)}%`;
                    
                    // Actualizar estado de la tarjeta
                    card.classList.remove('normal', 'warning', 'expired', 'paused');
                    
                    if (tiempoRestante > 0) {
                        if (tiempoRestante <= 600) { // 10 minutos = 600 segundos
                            card.classList.add('warning');
                            progressEl.style.background = '#FFC107';
                            
                            // Reproducir sonido de alerta cada minuto
                            if (soundOn && tiempoRestante % 60 === 0) {
                                alertSound.play();
                            }
                        } else {
                            card.classList.add('normal');
                            progressEl.style.background = '#4CAF50';
                        }
                    } else {
                        card.classList.add('expired');
                        progressEl.style.background = '#F44336';
                        
                        // Mostrar notificación y reproducir sonido
                        if (soundOn && !expiredClients.includes(clienteId)) {
                            expiredClients.push(clienteId);
                            alertSound.play();
                            notification.classList.add('show');
                            setTimeout(() => notification.classList.remove('show'), 5000);
                        }
                    }
                }, 1000);
            } else {
                // Si está pausado, mostrar tiempo actual
                const tiempoRestante = Math.max(0, tiempoMaximo - tiempoTranscurrido);
                timerEl.textContent = formatTime(tiempoRestante);
                
                // Actualizar barra de progreso
                const progressWidth = tiempoMaximo > 0 ? 
                    (tiempoTranscurrido / tiempoMaximo) * 100 : 0;
                progressEl.style.width = `${Math.min(100, progressWidth)}%`;
                
                // Actualizar estado visual de la tarjeta
                card.classList.remove('normal', 'warning', 'expired');
                card.classList.add('paused');
            }
        }
        
        // Botón para alternar sonido
        toggleSoundBtn.addEventListener('click', function() {
            soundOn = !soundOn;
            this.textContent = soundOn ? '🔈 Sonido: ON' : '🔇 Sonido: OFF';
        });
        
        // Iniciar todos los temporizadores al cargar
        document.querySelectorAll('.cliente-card').forEach(card => {
            const clienteId = card.dataset.id;
            iniciarTemporizador(clienteId);
        });
    });
    </script>
</body>
</html>