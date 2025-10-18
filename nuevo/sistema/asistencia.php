<?php
session_start();
require 'database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Verificar y crear columnas si no existen
try {
    $pdo->exec("ALTER TABLE asistencias ADD COLUMN IF NOT EXISTS reportada TINYINT(1) DEFAULT 0 NOT NULL");
} catch (PDOException $e) {
    if ($e->getCode() != '42S21') {
        die("Error al crear columna: " . $e->getMessage());
    }
}

try {
    $pdo->exec("ALTER TABLE asistencias ADD COLUMN IF NOT EXISTS actividad_id INT NOT NULL");
    $pdo->exec("ALTER TABLE asistencias ADD FOREIGN KEY (actividad_id) REFERENCES actividades(id) ON DELETE CASCADE");
} catch (PDOException $e) {
    if ($e->getCode() != '42S21' && $e->getCode() != '42000') {
        die("Error al crear columna actividad_id: " . $e->getMessage());
    }
}

$mensaje = isset($_SESSION['mensaje']) ? $_SESSION['mensaje'] : '';
$tipoMensaje = isset($_SESSION['tipo_mensaje']) ? $_SESSION['tipo_mensaje'] : '';
unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);

// Procesar búsqueda
$resultados = [];
$busqueda = $_POST['busqueda'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['buscar'])) {
    $termino = '%' . trim($busqueda) . '%';
    
    // CONSULTA CORREGIDA: Unir correctamente clientes y usuarios
    $stmt = $pdo->prepare("SELECT c.cedula, u.nombre, c.apellido, u.email, c.telefono 
                          FROM clientes c 
                          INNER JOIN usuarios u ON c.cedula = u.cedula 
                          WHERE (c.cedula LIKE ? OR u.nombre LIKE ? OR c.apellido LIKE ?)
                          AND u.estado = 'activo' 
                          ORDER BY u.nombre, c.apellido");
    $stmt->execute([$termino, $termino, $termino]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Registrar asistencia
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['registrar_asistencia'])) {
    $cliente_id = (int)$_POST['cliente_id'];
    $actividad_id = (int)$_POST['actividad_id'];
    $usuario_id = (int)$_SESSION['usuario_id'];
    
    // Verificar si el usuario existe
    $stmt = $pdo->prepare("SELECT cedula FROM usuarios WHERE cedula = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        $_SESSION['mensaje'] = "Error: Usuario no válido";
        $_SESSION['tipo_mensaje'] = 'danger';
        header('Location: asistencia.php');
        exit;
    }
    
    if ($cliente_id > 0 && $actividad_id > 0) {
        // VERIFICAR SI EL CLIENTE ESTÁ INSCRITO EN LA ACTIVIDAD
        $stmtVerificar = $pdo->prepare("SELECT COUNT(*) AS existe 
                                      FROM cliente_actividad 
                                      WHERE cliente_id = ? AND actividad_id = ?");
        $stmtVerificar->execute([$cliente_id, $actividad_id]);
        $resultado = $stmtVerificar->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado['existe'] > 0) {
            $stmt = $pdo->prepare("INSERT INTO asistencias (cliente_id, actividad_id, usuario_id) 
                                  VALUES (?, ?, ?)");
            if ($stmt->execute([$cliente_id, $actividad_id, $usuario_id])) {
                $_SESSION['mensaje'] = "Asistencia registrada correctamente";
                $_SESSION['tipo_mensaje'] = 'success';
            } else {
                $_SESSION['mensaje'] = "Error al registrar asistencia";
                $_SESSION['tipo_mensaje'] = 'danger';
            }
        } else {
            $_SESSION['mensaje'] = "El cliente no está inscrito en esta actividad";
            $_SESSION['tipo_mensaje'] = 'danger';
        }
        header('Location: asistencia.php');
        exit;
    } else {
        $_SESSION['mensaje'] = "Debe seleccionar una actividad";
        $_SESSION['tipo_mensaje'] = 'danger';
        header('Location: asistencia.php');
        exit;
    }
}

// Procesar marcar como reportadas
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['marcar_reportadas']) || isset($_POST['marcar_reportadas_por_actividad']))) {
    if (!empty($_POST['asistencia_ids'])) {
        $ids = $_POST['asistencia_ids'];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        $stmt = $pdo->prepare("UPDATE asistencias SET reportada = 1 WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        
        $_SESSION['mensaje'] = "Asistencias marcadas como reportadas correctamente";
        $_SESSION['tipo_mensaje'] = 'success';
    } else {
        $_SESSION['mensaje'] = "Seleccione al menos una asistencia para reportar";
        $_SESSION['tipo_mensaje'] = 'warning';
    }
    
    header('Location: asistencia.php');
    exit;
}

// Obtener todas las actividades disponibles
$stmtActividades = $pdo->query("SELECT id, nombre FROM actividades");
$actividades = $stmtActividades->fetchAll(PDO::FETCH_ASSOC);

// Obtener últimas asistencias no reportadas - CONSULTA CORREGIDA
$stmt = $pdo->query("SELECT a.id, a.fecha_hora, u.nombre, c.apellido, u2.nombre AS usuario, ac.nombre AS actividad
                    FROM asistencias a
                    JOIN clientes c ON a.cliente_id = c.cedula
                    JOIN usuarios u ON c.cedula = u.cedula
                    JOIN usuarios u2 ON a.usuario_id = u2.cedula
                    JOIN actividades ac ON a.actividad_id = ac.id
                    WHERE a.reportada = 0
                    ORDER BY a.fecha_hora DESC LIMIT 10");
$asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener asistencias agrupadas por actividad - CONSULTA CORREGIDA
$stmtPorActividad = $pdo->query("
    SELECT 
        a.id AS asistencia_id,
        ac.nombre AS actividad_nombre,
        a.fecha_hora,
        u.nombre AS cliente_nombre,
        c.apellido AS cliente_apellido,
        u2.nombre AS usuario_nombre
    FROM asistencias a
    JOIN clientes c ON a.cliente_id = c.cedula
    JOIN usuarios u ON c.cedula = u.cedula
    JOIN usuarios u2 ON a.usuario_id = u2.cedula
    JOIN actividades ac ON a.actividad_id = ac.id
    WHERE a.reportada = 0
    ORDER BY ac.nombre, a.fecha_hora DESC
");
$asistenciasPorActividad = $stmtPorActividad->fetchAll(PDO::FETCH_ASSOC);

// Agrupar por actividad
$asistenciasAgrupadas = [];
foreach ($asistenciasPorActividad as $asistencia) {
    $actividad = $asistencia['actividad_nombre'];
    if (!isset($asistenciasAgrupadas[$actividad])) {
        $asistenciasAgrupadas[$actividad] = [];
    }
    $asistenciasAgrupadas[$actividad][] = $asistencia;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Asistencia - Gimnasio</title>
    <link rel="stylesheet" href="css/diseño.css">
    <!-- Incluir bibliotecas para PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
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
        .btn-danger { background-color: var(--danger); color: white; }
        .btn-secondary { background-color: #95a5a6; color: white; }
        .btn-success { background-color: var(--success); color: white; }
        .btn-warning { background-color: var(--warning); color: white; }
        .btn-info { background-color: #3498db; color: white; }
        .btn-small { padding: 5px 10px; font-size: 14px; }
        
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
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: 500;
        }
        
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        
        .form-group {
            margin-bottom: 20px;
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
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
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
        
        .print-only { display: none; }
        @media print {
            .no-print { display: none; }
            .print-only { display: block; }
            body { background: white; }
            .container { width: 100%; padding: 0; }
            .sidebar, .header, .card-header .btn { display: none; }
            .main-content { margin-left: 0; }
            table { width: 100%; }
        }
        
        .asistencia-checkbox {
            display: block;
            width: 20px;
            height: 20px;
            margin: 0 auto;
        }
        
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
        }

        /* Estilos para la sección de asistencias por actividad */
        .actividad-section {
            margin-bottom: 30px;
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
        }
        .actividad-header {
            background-color: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            color: var(--secondary);
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .actividad-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .actividad-actions {
            display: flex;
            gap: 10px;
        }
        .actividad-header i {
            font-size: 24px;
        }
        .actividad-table {
            width: 100%;
        }
        .actividad-table th {
            background-color: #f1f2f6;
        }
        .actividad-table tr:last-child td {
            border-bottom: none;
        }
        
        /* Estilos para acciones */
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        .action-buttons button {
            padding: 5px 10px;
            font-size: 14px;
        }
        .global-actions {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .seleccionar-todas {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Mejoras para búsqueda */
        .search-info {
            background-color: #e8f4fd;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .no-resultados {
            text-align: center;
            padding: 20px;
            color: #7f8c8d;
            font-style: italic;
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
                <h1>Control de Asistencia</h1>
                <div class="user-info">
                    <span>Bienvenido, <?php echo $_SESSION['usuario_nombre']; ?></span>
                    <a href="logout.php" class="btn btn-danger">Cerrar Sesión</a>
                </div>
            </div>
            
            <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipoMensaje; ?>">
                <?php echo $mensaje; ?>
            </div>
            <?php endif; ?>
            
            <div class="card no-print">
                <div class="card-header">
                    <h3>Registrar Asistencia</h3>
                </div>
                <div class="card-body">
                    <form method="post" action="asistencia.php">
                        <div class="form-group">
                            <label for="busqueda">Buscar Cliente:</label>
                            <input type="text" id="busqueda" name="busqueda" class="form-control" 
                                   placeholder="Ingrese cédula, nombre o apellido del cliente" 
                                   value="<?php echo htmlspecialchars($busqueda); ?>" required>
                        </div>
                        <div class="search-info">
                            <small>Puede buscar por: cédula, nombre o apellido del cliente</small>
                        </div>
                        <div class="form-group">
                            <button type="submit" name="buscar" class="btn btn-primary">Buscar Cliente</button>
                        </div>
                    </form>
                    
                    <?php if (!empty($resultados)): ?>
                    <div class="resultados-busqueda">
                        <h4>Resultados de la búsqueda:</h4>
                        <table>
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Cédula</th>
                                    <th>Teléfono</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resultados as $cliente): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido']); ?></td>
                                    <td><?php echo htmlspecialchars($cliente['email']); ?></td>
                                    <td><?php echo htmlspecialchars($cliente['cedula']); ?></td>
                                    <td><?php echo htmlspecialchars($cliente['telefono']); ?></td>
                                    <td>
                                        <form method="post" action="asistencia.php">
                                            <input type="hidden" name="cliente_id" value="<?php echo $cliente['cedula']; ?>">
                                            <div class="form-group">
                                                <label for="actividad_<?php echo $cliente['cedula']; ?>">Actividad:</label>
                                                <select id="actividad_<?php echo $cliente['cedula']; ?>" name="actividad_id" class="form-control" required>
                                                    <option value="">Seleccione una actividad</option>
                                                    <?php foreach ($actividades as $actividad): ?>
                                                    <option value="<?php echo $actividad['id']; ?>"><?php echo htmlspecialchars($actividad['nombre']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <button type="submit" name="registrar_asistencia" class="btn btn-success">
                                                    Registrar Asistencia
                                                </button>
                                                <a href="historial_asistencia.php?cedula=<?php echo $cliente['cedula']; ?>" class="btn btn-info">
                                                    Ver Historial
                                                </a>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['buscar'])): ?>
                    <p class="no-resultados">No se encontraron clientes activos con esos datos.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Sección de Últimas Asistencias Registradas -->
            <div class="card">
                <div class="card-header">
                    <h3>Últimas Asistencias Registradas</h3>
                    <div class="no-print">
                        <button onclick="window.print()" class="btn btn-secondary">Imprimir</button>
                        <button id="btn-descargar-pdf" class="btn btn-success">Descargar PDF</button>
                    </div>
                </div>
                <div class="card-body">
                    <form method="post" action="asistencia.php" id="form-reportes">
                        <?php if (!empty($asistencias)): ?>
                        <div class="print-only">
                            <h2>Reporte de Asistencias</h2>
                            <p>Gimnasio - <?php echo date('d/m/Y H:i'); ?></p>
                            <p>Generado por: <?php echo $_SESSION['usuario_nombre']; ?></p>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th class="no-print" style="width: 30px;"></th>
                                    <th>Fecha/Hora</th>
                                    <th>Cliente</th>
                                    <th>Actividad</th>
                                    <th>Registrado por</th>
                                    <th class="no-print" style="width: 100px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($asistencias as $asistencia): 
                                    $actividadClass = 'tag-' . strtolower($asistencia['actividad']);
                                ?>
                                <tr>
                                    <td class="no-print" style="text-align: center;">
                                        <input type="checkbox" name="asistencia_ids[]" 
                                               value="<?php echo $asistencia['id']; ?>" 
                                               class="asistencia-checkbox">
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($asistencia['fecha_hora'])); ?></td>
                                    <td><?php echo htmlspecialchars($asistencia['nombre'] . ' ' . $asistencia['apellido']); ?></td>
                                    <td>
                                        <span class="activity-tag <?php echo $actividadClass; ?>">
                                            <?php echo htmlspecialchars($asistencia['actividad']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($asistencia['usuario']); ?></td>
                                    <td class="no-print">
                                        <div class="action-buttons">
                                            <button type="button" class="btn btn-danger btn-small btn-eliminar" 
                                                    data-id="<?php echo $asistencia['id']; ?>">
                                                Eliminar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div class="no-print global-actions">
                            <div class="seleccionar-todas">
                                <input type="checkbox" id="seleccionar-todos">
                                <label for="seleccionar-todos">Seleccionar todas</label>
                            </div>
                            <div>
                                <button type="submit" name="marcar_reportadas" class="btn btn-warning">
                                    Marcar como Reportadas
                                </button>
                            </div>
                        </div>
                        <?php else: ?>
                        <p class="no-resultados">No hay asistencias registradas aún.</p>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Sección de Asistencias por Actividad -->
            <div class="card">
                <div class="card-header">
                    <h3>Asistencias por Actividad</h3>
                </div>
                <div class="card-body">
                    <form method="post" action="asistencia.php" id="form-reportes-por-actividad">
                        <?php if (!empty($asistenciasAgrupadas)): ?>
                            <?php foreach ($asistenciasAgrupadas as $actividad => $asistencias): ?>
                                <div class="actividad-section" id="actividad-<?php echo urlencode($actividad); ?>">
                                    <div class="actividad-header">
                                        <div class="actividad-title">
                                            <?php 
                                                $icon = "";
                                                if (stripos($actividad, 'gimnasio') !== false) $icon = "💪";
                                                elseif (stripos($actividad, 'boxeo') !== false) $icon = "🥊";
                                                elseif (stripos($actividad, 'taekwondo') !== false) $icon = "🥋";
                                                elseif (stripos($actividad, 'spinning') !== false) $icon = "🚴";
                                                else $icon = "🏅";
                                            ?>
                                            <i><?php echo $icon; ?></i>
                                            <span><?php echo htmlspecialchars($actividad); ?></span>
                                        </div>
                                        <div class="actividad-actions no-print">
                                            <button type="button" class="btn btn-secondary btn-imprimir-actividad" 
                                                    data-actividad="<?php echo htmlspecialchars($actividad); ?>">
                                                Imprimir
                                            </button>
                                            <button type="button" class="btn btn-success btn-pdf-actividad" 
                                                    data-actividad="<?php echo htmlspecialchars($actividad); ?>">
                                                Descargar PDF
                                            </button>
                                        </div>
                                    </div>
                                    <table class="actividad-table">
                                        <thead>
                                            <tr>
                                                <th class="no-print" style="width: 30px;">
                                                    <input type="checkbox" class="seleccionar-todos-actividad" 
                                                           data-actividad="<?php echo htmlspecialchars($actividad); ?>">
                                                </th>
                                                <th>Fecha/Hora</th>
                                                <th>Cliente</th>
                                                <th>Registrado por</th>
                                                <th class="no-print" style="width: 100px;">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($asistencias as $asistencia): ?>
                                            <tr>
                                                <td class="no-print" style="text-align: center;">
                                                    <input type="checkbox" name="asistencia_ids[]" 
                                                           value="<?php echo $asistencia['asistencia_id']; ?>" 
                                                           class="asistencia-checkbox" 
                                                           data-actividad="<?php echo htmlspecialchars($actividad); ?>">
                                                </td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($asistencia['fecha_hora'])); ?></td>
                                                <td><?php echo htmlspecialchars($asistencia['cliente_nombre'] . ' ' . $asistencia['cliente_apellido']); ?></td>
                                                <td><?php echo htmlspecialchars($asistencia['usuario_nombre']); ?></td>
                                                <td class="no-print">
                                                    <div class="action-buttons">
                                                        <button type="button" class="btn btn-danger btn-small btn-eliminar" 
                                                                data-id="<?php echo $asistencia['asistencia_id']; ?>">
                                                            Eliminar
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endforeach; ?>
                            <div class="no-print global-actions">
                                <div class="seleccionar-todas">
                                    <input type="checkbox" id="seleccionar-todas-actividades">
                                    <label for="seleccionar-todas-actividades">Seleccionar todas</label>
                                </div>
                                <div>
                                    <button type="submit" name="marcar_reportadas_por_actividad" class="btn btn-warning">
                                        Marcar como Reportadas
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="no-resultados">No hay asistencias registradas por actividad.</p>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenedores ocultos para PDF -->
    <div id="pdf-container" style="display: none; padding: 20px;">
        <h2 style="text-align: center;">Reporte de Asistencias</h2>
        <p style="text-align: center;">
            Gimnasio<br>
            Fecha: <?php echo date('d/m/Y H:i'); ?><br>
            Generado por: <?php echo $_SESSION['usuario_nombre']; ?>
        </p>
        <?php if (!empty($asistencias)): ?>
        <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
            <thead>
                <tr>
                    <th style="border: 1px solid #000; padding: 8px;">Fecha/Hora</th>
                    <th style="border: 1px solid #000; padding: 8px;">Cliente</th>
                    <th style="border: 1px solid #000; padding: 8px;">Actividad</th>
                    <th style="border: 1px solid #000; padding: 8px;">Registrado por</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($asistencias as $asistencia): ?>
                <tr>
                    <td style="border: 1px solid #000; padding: 8px;"><?php echo date('d/m/Y H:i', strtotime($asistencia['fecha_hora'])); ?></td>
                    <td style="border: 1px solid #000; padding: 8px;"><?php echo htmlspecialchars($asistencia['nombre'] . ' ' . $asistencia['apellido']); ?></td>
                    <td style="border: 1px solid #000; padding: 8px;"><?php echo htmlspecialchars($asistencia['actividad']); ?></td>
                    <td style="border: 1px solid #000; padding: 8px;"><?php echo htmlspecialchars($asistencia['usuario']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p>No hay asistencias registradas aún.</p>
        <?php endif; ?>
    </div>

    <?php foreach ($asistenciasAgrupadas as $actividad => $asistencias): ?>
    <div id="pdf-container-<?php echo urlencode($actividad); ?>" style="display: none; padding: 20px;">
        <h2 style="text-align: center;">Reporte de Asistencias: <?php echo htmlspecialchars($actividad); ?></h2>
        <p style="text-align: center;">
            Gimnasio<br>
            Fecha: <?php echo date('d/m/Y H:i'); ?><br>
            Generado por: <?php echo $_SESSION['usuario_nombre']; ?>
        </p>
        <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
            <thead>
                <tr>
                    <th style="border: 1px solid #000; padding: 8px;">Fecha/Hora</th>
                    <th style="border: 1px solid #000; padding: 8px;">Cliente</th>
                    <th style="border: 1px solid #000; padding: 8px;">Registrado por</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($asistencias as $asistencia): ?>
                <tr>
                    <td style="border: 1px solid #000; padding: 8px;"><?php echo date('d/m/Y H:i', strtotime($asistencia['fecha_hora'])); ?></td>
                    <td style="border: 1px solid #000; padding: 8px;"><?php echo htmlspecialchars($asistencia['cliente_nombre'] . ' ' . $asistencia['cliente_apellido']); ?></td>
                    <td style="border: 1px solid #000; padding: 8px;"><?php echo htmlspecialchars($asistencia['usuario_nombre']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>

    <script>
    // Función para descargar el reporte como PDF (para últimas asistencias)
    document.getElementById('btn-descargar-pdf').addEventListener('click', function() {
        const element = document.getElementById('pdf-container');
        const clone = element.cloneNode(true);
        clone.style.display = 'block';
        clone.style.padding = '20px';
        
        document.body.appendChild(clone);
        
        html2canvas(clone).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jspdf.jsPDF('p', 'mm', 'a4');
            const imgWidth = 210;
            const pageHeight = 297;
            const imgHeight = canvas.height * imgWidth / canvas.width;
            let heightLeft = imgHeight;
            let position = 0;
            
            pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;
            
            while (heightLeft > 0) {
                position = heightLeft - imgHeight + 20;
                pdf.addPage();
                pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;
            }
            
            pdf.save('reporte_asistencias_' + new Date().toISOString().slice(0, 10) + '.pdf');
            document.body.removeChild(clone);
        });
    });

    // Función para generar PDF por actividad
    function generarPDFPorActividad(actividad) {
        const elementId = `pdf-container-${actividad}`;
        const element = document.getElementById(elementId);
        
        if (!element) {
            alert('No se encontró el reporte para esta actividad');
            return;
        }
        
        const clone = element.cloneNode(true);
        clone.style.display = 'block';
        clone.style.padding = '20px';
        
        document.body.appendChild(clone);
        
        html2canvas(clone).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jspdf.jsPDF('p', 'mm', 'a4');
            const imgWidth = 210;
            const pageHeight = 297;
            const imgHeight = canvas.height * imgWidth / canvas.width;
            let heightLeft = imgHeight;
            let position = 0;
            
            pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;
            
            while (heightLeft > 0) {
                position = heightLeft - imgHeight + 20;
                pdf.addPage();
                pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;
            }
            
            pdf.save(`asistencias_${actividad}_${new Date().toISOString().slice(0, 10)}.pdf`);
            document.body.removeChild(clone);
        });
    }

    // Función para imprimir por actividad
    function imprimirPorActividad(actividad) {
        const originalContent = document.body.innerHTML;
        const actividadElement = document.getElementById(`actividad-${actividad}`);
        
        if (!actividadElement) {
            alert('No se encontró la actividad');
            return;
        }
        
        const printContent = actividadElement.outerHTML;
        
        document.body.innerHTML = `
            <style>
                @media print {
                    body { background: white; }
                    .actividad-section { border: none; }
                    .no-print { display: none; }
                    .actividad-table th { background-color: #f1f2f6 !important; }
                }
            </style>
            <div style="text-align:center;margin-bottom:20px;">
                <h1>Reporte de Asistencias: ${decodeURIComponent(actividad)}</h1>
                <p>Fecha: ${new Date().toLocaleDateString()}</p>
                <p>Generado por: <?php echo $_SESSION['usuario_nombre']; ?></p>
            </div>
            ${printContent}
        `;
        
        window.print();
        document.body.innerHTML = originalContent;
    }

    // Event listeners para botones de actividad
    document.querySelectorAll('.btn-pdf-actividad').forEach(btn => {
        btn.addEventListener('click', function() {
            const actividad = this.getAttribute('data-actividad');
            generarPDFPorActividad(encodeURIComponent(actividad));
        });
    });

    document.querySelectorAll('.btn-imprimir-actividad').forEach(btn => {
        btn.addEventListener('click', function() {
            const actividad = this.getAttribute('data-actividad');
            imprimirPorActividad(encodeURIComponent(actividad));
        });
    });

    // Selección/deselección
    document.getElementById('seleccionar-todos').addEventListener('change', function(e) {
        document.querySelectorAll('#form-reportes .asistencia-checkbox').forEach(checkbox => {
            checkbox.checked = e.target.checked;
        });
    });

    document.getElementById('seleccionar-todas-actividades').addEventListener('change', function(e) {
        document.querySelectorAll('#form-reportes-por-actividad .asistencia-checkbox').forEach(checkbox => {
            checkbox.checked = e.target.checked;
        });
    });

    document.querySelectorAll('.seleccionar-todos-actividad').forEach(checkbox => {
        checkbox.addEventListener('change', function(e) {
            const actividad = this.getAttribute('data-actividad');
            document.querySelectorAll(`.asistencia-checkbox[data-actividad="${actividad}"]`).forEach(cb => {
                cb.checked = e.target.checked;
            });
        });
    });

    // Eliminación individual
    document.querySelectorAll('.btn-eliminar').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            if (confirm('¿Está seguro de que desea marcar esta asistencia como reportada?')) {
                // Crear formulario dinámico para enviar la solicitud
                const form = document.createElement('form');
                form.method = 'post';
                form.action = 'asistencia.php';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'asistencia_ids[]';
                input.value = id;
                form.appendChild(input);
                
                const submit = document.createElement('input');
                submit.type = 'hidden';
                submit.name = 'marcar_reportadas';
                submit.value = '1';
                form.appendChild(submit);
                
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
    </script>
</body>
</html>