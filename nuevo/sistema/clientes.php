<?php
session_start();
require 'database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$cedula = isset($_GET['cedula']) ? $_GET['cedula'] : 0;

// Mensajes
$mensaje = isset($_SESSION['mensaje']) ? $_SESSION['mensaje'] : '';
$tipoMensaje = isset($_SESSION['tipo_mensaje']) ? $_SESSION['tipo_mensaje'] : '';
unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);

// Obtener datos para editar
$cliente = [];
$actividadesCliente = [];

if ($action == 'edit' && !empty($cedula)) {
    // Obtener datos del cliente usando la vista
    $stmt = $pdo->prepare("SELECT * FROM vista_clientes_completa WHERE cedula = ?");
    $stmt->execute([$cedula]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cliente) {
        // Obtener actividades del cliente
        $stmt = $pdo->prepare("SELECT actividad_id FROM cliente_actividad WHERE cliente_id = ?");
        $stmt->execute([$cedula]);
        $actividadesCliente = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    } else {
        $action = 'list';
    }
}

// Obtener todas las actividades disponibles
$stmt = $pdo->query("SELECT id, nombre FROM actividades");
$actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener usuarios con rol 'cliente' que no están registrados en la tabla clientes (solo activos)
if ($action == 'add') {
    $stmt = $pdo->query("
        SELECT u.cedula, u.nombre, u.apellido,u.email, u.fecha_nacimiento 
        FROM usuarios u 
        WHERE u.rol = 'cliente' 
        AND u.cedula NOT IN (SELECT cedula FROM clientes)
        AND u.estado = 'activo'
        ORDER BY u.nombre
    ");
    $usuarios_no_registrados = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Listar clientes (solo activos)
if ($action == 'list') {
    $stmt = $pdo->query("SELECT * FROM vista_clientes_completa WHERE estado = 'activo' ORDER BY fecha_registro DESC");
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener actividades para cada cliente
    foreach ($clientes as &$cl) {
        $stmt = $pdo->prepare("SELECT a.nombre 
                              FROM actividades a
                              JOIN cliente_actividad ca ON a.id = ca.actividad_id
                              WHERE ca.cliente_id = ?");
        $stmt->execute([$cl['cedula']]);
        $cl['actividades'] = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }
    unset($cl); // Romper la referencia
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clientes - Gimnasio</title>
    <link rel="stylesheet" href="css/diseño.css">
    <style>
        /* Mantener todos los estilos CSS existentes */
        :root {
            --primary: #1DCD9F;
            --secondary: #169976;
            --success: #27ae60;
            --danger: #e74c3c;
            --warning: #f39c12;
            --light: #ecf0f1;
            --dark: #2c3e50;
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
            background: linear-gradient(135deg, #000000, #222222, #1DCD9F);
            color: white;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            transition: all 0.3s;
            position: fixed;
            height: 100%;
        }
        
        .sidebar-header {
            padding: 20px;
            background-color: rgba(0,0,0,0.2);
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
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
            transition: all 0.3s;
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
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(29, 205, 159, 0.3);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
            color: white;
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(149, 165, 166, 0.3);
        }
        
        .btn-info {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }
        
        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }
        
        .card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .card-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        }
        
        .card-body {
            padding: 25px;
        }
        
        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
            border-left: 4px solid;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border-color: #bee5eb;
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-col {
            flex: 1;
            min-width: 250px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 16px;
            transition: all 0.3s;
            background-color: #f8f9fa;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            background-color: white;
            outline: none;
            box-shadow: 0 0 0 3px rgba(29, 205, 159, 0.2);
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
        }
        
        table th, table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        table th {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            font-weight: 600;
            color: var(--dark);
        }
        
        table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .actions {
            display: flex;
            gap: 10px;
        }
        
        .action-btn {
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .action-edit {
            background-color: var(--primary);
            color: white;
        }
        
        .action-edit:hover {
            background-color: var(--secondary);
            transform: translateY(-1px);
        }
        
        .action-delete {
            background-color: var(--danger);
            color: white;
        }
        
        .action-delete:hover {
            background-color: #c0392b;
            transform: translateY(-1px);
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
        
        .tag-gimnasio {
            background-color: #3498db;
        }
        
        .tag-boxeo {
            background-color: #e74c3c;
        }
        
        .tag-taekwondo {
            background-color: #27ae60;
        }
        
        .tag-spinning {
            background-color: #f39c12;
        }
        
        .text-muted {
            color: #7f8c8d;
        }
        
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f8f9fa;
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }
        
        .checkbox-item input {
            margin: 0;
        }
        
        .membresia-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .membresia-basica {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .membresia-premium {
            background-color: #fff3e0;
            color: #f57c00;
        }
        
        .membresia-vip {
            background-color: #f3e5f5;
            color: #7b1fa2;
        }
        
        .usuario-selector {
            background-color: #e8f5e8;
            border-left: 4px solid var(--primary);
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar-header h2, .sidebar-menu li a span {
                display: none;
            }
            
            .sidebar-menu li a i {
                margin-right: 0;
                font-size: 24px;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .form-col {
                flex: 100%;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Gimnasio</h2>
            </div>
            <div class="sidebar-menu">
                <ul>
                    <li><a href="index.php"><i>🏠</i> <span>Inicio</span></a></li>
                    <li><a href="clientes.php" class="active"><i>👥</i> <span>Clientes</span></a></li>
                    <li><a href="asistencia.php"><i>✅</i> <span>Asistencia</span></a></li>
                    <li><a href="monitoreo.php"><i>⏱️</i> <span>Monitoreo</span></a></li> 
                    <li><a href="usuarios.php"><i>👤</i> <span>Usuarios</span></a></li>
                    <li><a href="escanner.php"><i>📷</i> <span>Escanear QR</span></a></li>
                    <li><a href="logout.php"><i>🔒</i> <span>Cerrar Sesión</span></a></li>
                </ul>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1><?php echo $action == 'add' ? 'Agregar Cliente' : ($action == 'edit' ? 'Editar Cliente' : 'Clientes'); ?></h1>
                <div class="user-info">
                    <span>Bienvenido, <?php echo $_SESSION['usuario_nombre']; ?> (<?php echo $_SESSION['usuario_rol']; ?>)</span>
                    <a href="logout.php" class="btn btn-danger">Cerrar Sesión</a>
                </div>
            </div>
            
            <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipoMensaje; ?>">
                <?php echo $mensaje; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($action == 'add' || $action == 'edit'): ?>
            <!-- Formulario de cliente -->
            <div class="card">
                <div class="card-header">
                    <h3><?php echo $action == 'add' ? 'Agregar Nuevo Cliente' : 'Editar Cliente'; ?></h3>
                </div>
                <div class="card-body">
                    <form method="post" action="acciones/cliente_guardar.php">
                        <input type="hidden" name="cedula" value="<?php echo isset($cliente['cedula']) ? $cliente['cedula'] : ''; ?>">
                        
                        <?php if ($action == 'add'): ?>
                            <?php if (!empty($usuarios_no_registrados)): ?>
                            <div class="usuario-selector">
                                <div class="form-group">
                                    <label for="seleccionar_usuario">Seleccionar Usuario Existente (Opcional)</label>
                                    <select id="seleccionar_usuario" class="form-control" onchange="cargarDatosUsuario(this.value)">
                                        <option value="">-- Seleccionar usuario existente --</option>
                                        <?php foreach ($usuarios_no_registrados as $usuario): ?>
                                        <option value="<?php echo $usuario['cedula']; ?>">
                                            <?php echo $usuario['cedula'] . ' - ' . $usuario['nombre'] . ' ' . $usuario['apellido'] . ' (' . $usuario['email'] . ')'; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">
                                        💡 Selecciona un usuario existente con rol 'cliente' que aún no tenga datos completos como cliente. 
                                        Se autocompletarán los datos básicos.
                                    </small>
                                </div>
                                <div style="margin-top: 10px;">
                                    <button type="button" class="btn btn-info" onclick="limpiarSeleccion()">🔄 Limpiar Selección</button>
                                    <small class="text-muted" style="margin-left: 10px;">
                                        Usuarios disponibles: <?php echo count($usuarios_no_registrados); ?>
                                    </small>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info">
                                ℹ️ No hay usuarios disponibles con rol 'cliente' que no estén registrados como clientes. 
                                Puede <a href="usuarios.php?action=add">crear un nuevo usuario</a> primero o registrar los datos manualmente.
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="cedula">Cédula *</label>
                                    <input type="text" id="cedula" name="cedula" class="form-control" 
                                           value="<?php echo isset($cliente['cedula']) ? $cliente['cedula'] : ''; ?>" 
                                           <?php echo $action == 'edit' ? 'readonly' : 'required'; ?>>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="fecha_nacimiento">Fecha de Nacimiento *</label>
                                    <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" class="form-control" 
                                           value="<?php echo isset($cliente['fecha_nacimiento']) ? $cliente['fecha_nacimiento'] : ''; ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="nombre">Nombre *</label>
                                    <input type="text" id="nombre" name="nombre" class="form-control" 
                                           value="<?php echo isset($cliente['nombre']) ? $cliente['nombre'] : ''; ?>" required>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="apellido">Apellido *</label>
                                    <input type="text" id="apellido" name="apellido" class="form-control" 
                                           value="<?php echo isset($cliente['apellido']) ? $cliente['apellido'] : ''; ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="email">Correo Electrónico *</label>
                                    <input type="email" id="email" name="email" class="form-control" 
                                           value="<?php echo isset($cliente['email']) ? $cliente['email'] : ''; ?>" required>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="telefono">Teléfono</label>
                                    <input type="text" id="telefono" name="telefono" class="form-control" 
                                           value="<?php echo isset($cliente['telefono']) ? $cliente['telefono'] : ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="direccion">Dirección</label>
                            <textarea id="direccion" name="direccion" class="form-control"><?php echo isset($cliente['direccion']) ? $cliente['direccion'] : ''; ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="tipo_membresia">Tipo de Membresía *</label>
                                    <select id="tipo_membresia" name="tipo_membresia" class="form-control" required>
                                        <option value="basica" <?php echo (isset($cliente['tipo_membresia']) && $cliente['tipo_membresia'] == 'basica') ? 'selected' : ''; ?>>Básica</option>
                                        <option value="premium" <?php echo (isset($cliente['tipo_membresia']) && $cliente['tipo_membresia'] == 'premium') ? 'selected' : ''; ?>>Premium</option>
                                        <option value="vip" <?php echo (isset($cliente['tipo_membresia']) && $cliente['tipo_membresia'] == 'vip') ? 'selected' : ''; ?>>VIP</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="fecha_inicio">Fecha de Inicio *</label>
                                    <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control" 
                                           value="<?php echo isset($cliente['fecha_inicio']) ? $cliente['fecha_inicio'] : date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="fecha_vencimiento">Fecha de Vencimiento *</label>
                                    <input type="date" id="fecha_vencimiento" name="fecha_vencimiento" class="form-control" 
                                           value="<?php echo isset($cliente['fecha_vencimiento']) ? $cliente['fecha_vencimiento'] : ''; ?>" required>
                                </div>
                            </div>
                            <div class="form-col">
                                <!-- Espacio vacío -->
                            </div>
                        </div>
                        
                        <!-- Campo de actividades (múltiple) -->
                        <div class="form-group">
                            <label>Actividades</label>
                            <div class="checkbox-group">
                                <?php foreach ($actividades as $actividad): 
                                    $checked = in_array($actividad['id'], $actividadesCliente) ? 'checked' : '';
                                ?>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="actividad_<?php echo $actividad['id']; ?>" 
                                           name="actividades[]" value="<?php echo $actividad['id']; ?>" <?php echo $checked; ?>>
                                    <label for="actividad_<?php echo $actividad['id']; ?>"><?php echo ucfirst($actividad['nombre']); ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>QR de Asistencia</label>
                            <div>
                                <?php if (!empty($cliente['cedula'])): ?>
                                <a href="qr_generar.php?cedula=<?php echo $cliente['cedula']; ?>" target="_blank" class="btn btn-secondary">
                                    📱 Ver/Descargar QR
                                </a>
                                <?php else: ?>
                                <p class="text-muted">El QR se generará después de guardar el cliente</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">💾 Guardar Cliente</button>
                            <a href="clientes.php" class="btn btn-danger">❌ Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <!-- Lista de clientes -->
            <div class="card">
                <div class="card-header">
                    <h3>👥 Lista de Clientes</h3>
                    <a href="clientes.php?action=add" class="btn btn-primary">➕ Agregar Cliente</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($clientes)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Cédula</th>
                                <th>Nombre Completo</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Membresía</th>
                                <th>Actividades</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clientes as $c): ?>
                            <tr>
                                <td><?php echo $c['cedula']; ?></td>
                                <td><?php echo $c['nombre_completo']; ?></td>
                                <td><?php echo $c['email']; ?></td>
                                <td><?php echo $c['telefono'] ?: 'N/A'; ?></td>
                                <td>
                                    <span class="membresia-badge membresia-<?php echo $c['tipo_membresia']; ?>">
                                        <?php echo ucfirst($c['tipo_membresia']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php foreach ($c['actividades'] as $actividad): 
                                        $actividadClass = 'tag-' . $actividad;
                                    ?>
                                        <span class="activity-tag <?php echo $actividadClass; ?>"><?php echo ucfirst($actividad); ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td class="actions">
                                    <a href="clientes.php?action=edit&cedula=<?php echo $c['cedula']; ?>" class="action-btn action-edit">✏️ Editar</a>
                                    <a href="acciones/cliente_eliminar.php?cedula=<?php echo $c['cedula']; ?>" class="action-btn action-delete" onclick="return confirm('¿Estás seguro de eliminar este cliente?');">🗑️ Eliminar</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div style="text-align: center; padding: 40px;">
                        <p style="font-size: 18px; color: #7f8c8d;">No hay clientes registrados</p>
                        <a href="clientes.php?action=add" class="btn btn-primary" style="margin-top: 15px;">➕ Agregar Primer Cliente</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // Función para cargar datos del usuario seleccionado
    function cargarDatosUsuario(cedula) {
        if (!cedula) return;
        
        // Mostrar indicador de carga
        const seleccionarUsuario = document.getElementById('seleccionar_usuario');
        const originalText = seleccionarUsuario.options[seleccionarUsuario.selectedIndex].text;
        seleccionarUsuario.options[seleccionarUsuario.selectedIndex].text = '🔄 Cargando...';
        
        fetch(`acciones/obtener_usuario.php?cedula=${cedula}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Llenar los campos del formulario con los datos del usuario
                    document.getElementById('cedula').value = data.usuario.cedula;
                    document.getElementById('nombre').value = data.usuario.nombre;
                    document.getElementById('apellido').value = data.usuario.apellido || '';
                    document.getElementById('email').value = data.usuario.email;
                    document.getElementById('fecha_nacimiento').value = data.usuario.fecha_nacimiento;
                    
                    // Hacer el campo de cédula de solo lectura
                    document.getElementById('cedula').setAttribute('readonly', true);
                    
                    // Deshabilitar el selector para evitar cambios
                    seleccionarUsuario.disabled = true;
                    
                    // Mostrar mensaje de éxito
                    showAlert('✅ Datos del usuario cargados correctamente. Complete la información restante.', 'success');
                } else {
                    showAlert('❌ Error al cargar los datos del usuario: ' + data.message, 'danger');
                    seleccionarUsuario.value = '';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('❌ Error al cargar los datos del usuario.', 'danger');
                seleccionarUsuario.value = '';
            })
            .finally(() => {
                // Restaurar texto original
                seleccionarUsuario.options[seleccionarUsuario.selectedIndex].text = originalText;
            });
    }

    // Función para limpiar la selección de usuario
    function limpiarSeleccion() {
        const seleccionarUsuario = document.getElementById('seleccionar_usuario');
        seleccionarUsuario.value = '';
        seleccionarUsuario.disabled = false;
        
        document.getElementById('cedula').removeAttribute('readonly');
        document.getElementById('cedula').value = '';
        document.getElementById('nombre').value = '';
        document.getElementById('apellido').value = '';
        document.getElementById('email').value = '';
        document.getElementById('fecha_nacimiento').value = '';
        
        showAlert('🔄 Selección limpiada. Puede ingresar manualmente los datos o seleccionar otro usuario.', 'info');
    }

    // Función para mostrar alertas temporales
    function showAlert(mensaje, tipo) {
        // Crear elemento de alerta
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${tipo}`;
        alertDiv.textContent = mensaje;
        
        // Insertar antes del formulario
        const cardBody = document.querySelector('.card-body');
        cardBody.insertBefore(alertDiv, cardBody.firstChild);
        
        // Remover después de 5 segundos
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }

    // Validación de fechas
    document.addEventListener('DOMContentLoaded', function() {
        const fechaInicio = document.getElementById('fecha_inicio');
        const fechaVencimiento = document.getElementById('fecha_vencimiento');
        
        if (fechaInicio && fechaVencimiento) {
            fechaInicio.addEventListener('change', function() {
                if (fechaInicio.value && !fechaVencimiento.value) {
                    // Establecer fecha de vencimiento por defecto (1 año después)
                    const fecha = new Date(fechaInicio.value);
                    fecha.setFullYear(fecha.getFullYear() + 1);
                    fechaVencimiento.value = fecha.toISOString().split('T')[0];
                }
            });
        }
        
        // Validación de cédula (solo números)
        const cedulaInput = document.getElementById('cedula');
        if (cedulaInput) {
            cedulaInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        }
    });
    </script>
</body>
</html>