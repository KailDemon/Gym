<?php
session_start();
require 'database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Asegurar que la columna 'cedula' exista
try {
    $pdo->exec("ALTER TABLE clientes ADD COLUMN IF NOT EXISTS cedula VARCHAR(20) DEFAULT ''");
} catch (PDOException $e) {
    if ($e->getCode() != '42S21') {
        die("Error al crear columna cédula: " . $e->getMessage());
    }
}

// Crear tabla de actividades si no existe
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS actividades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(50) NOT NULL UNIQUE
    )");
    
    // Insertar actividades por defecto si no existen
    $actividadesBase = ['gimnasio', 'boxeo', 'taekwondo', 'spinning'];
    foreach ($actividadesBase as $actividad) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO actividades (nombre) VALUES (?)");
        $stmt->execute([$actividad]);
    }
    
    // Crear tabla intermedia para relación muchos a muchos
    $pdo->exec("CREATE TABLE IF NOT EXISTS cliente_actividad (
        cliente_id INT NOT NULL,
        actividad_id INT NOT NULL,
        PRIMARY KEY (cliente_id, actividad_id),
        FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
        FOREIGN KEY (actividad_id) REFERENCES actividades(id) ON DELETE CASCADE
    )");
} catch (PDOException $e) {
    die("Error al crear tablas de actividades: " . $e->getMessage());
}

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Mensajes
$mensaje = isset($_SESSION['mensaje']) ? $_SESSION['mensaje'] : '';
$tipoMensaje = isset($_SESSION['tipo_mensaje']) ? $_SESSION['tipo_mensaje'] : '';
unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);

// Obtener datos para editar
$cliente = [];
$actividadesCliente = [];

if ($action == 'edit' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cliente) {
        // Obtener actividades del cliente
        $stmt = $pdo->prepare("SELECT actividad_id FROM cliente_actividad WHERE cliente_id = ?");
        $stmt->execute([$id]);
        $actividadesCliente = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    } else {
        $action = 'list';
    }
}

// Obtener todas las actividades disponibles
$stmt = $pdo->query("SELECT id, nombre FROM actividades");
$actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Listar clientes
if ($action == 'list') {
    $stmt = $pdo->query("SELECT * FROM clientes ORDER BY fecha_registro DESC");
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener actividades para cada cliente
    foreach ($clientes as &$cl) {
        $stmt = $pdo->prepare("SELECT a.nombre 
                              FROM actividades a
                              JOIN cliente_actividad ca ON a.id = ca.actividad_id
                              WHERE ca.cliente_id = ?");
        $stmt->execute([$cl['id']]);
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
            transition: all 0.3s;
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
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
        }
        
        .btn-danger {
            background-color: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
        }
        
        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #7f8c8d;
        }
        
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .card-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
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
        
        table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .actions {
            display: flex;
            gap: 10px;
        }
        
        .action-btn {
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .action-edit {
            background-color: #3498db;
            color: white;
        }
        
        .action-edit:hover {
            background-color: #2980b9;
        }
        
        .action-delete {
            background-color: #e74c3c;
            color: white;
        }
        
        .action-delete:hover {
            background-color: #c0392b;
        }
        
        .activity-tag {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
            margin-right: 5px;
            margin-bottom: 5px;
        }
        
        .tag-gimnasio {
            background-color: #3498db;
            color: white;
        }
        
        .tag-boxeo {
            background-color: #e74c3c;
            color: white;
        }
        
        .tag-taekwondo {
            background-color: #27ae60;
            color: white;
        }
        
        .tag-spinning {
            background-color: #f39c12;
            color: white;
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
        }
        
        .checkbox-item input {
            margin: 0;
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
                    <span>Bienvenido, <?php echo $_SESSION['usuario_nombre']; ?></span>
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
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="cedula">Cédula</label>
                                    <input type="text" id="cedula" name="cedula" class="form-control" 
                                           value="<?php echo isset($cliente['cedula']) ? $cliente['cedula'] : ''; ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="nombre">Nombre</label>
                                    <input type="text" id="nombre" name="nombre" class="form-control" 
                                           value="<?php echo isset($cliente['nombre']) ? $cliente['nombre'] : ''; ?>" required>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="apellido">Apellido</label>
                                    <input type="text" id="apellido" name="apellido" class="form-control" 
                                           value="<?php echo isset($cliente['apellido']) ? $cliente['apellido'] : ''; ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="email">Correo Electrónico</label>
                                    <input type="email" id="email" name="email" class="form-control" 
                                           value="<?php echo isset($cliente['email']) ? $cliente['email'] : ''; ?>" required>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="telefono">Teléfono</label>
                                    <input type="text" id="telefono" name="telefono" class="form-control" 
                                           value="<?php echo isset($cliente['telefono']) ? $cliente['telefono'] : ''; ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="direccion">Dirección</label>
                            <textarea id="direccion" name="direccion" class="form-control" required><?php echo isset($cliente['direccion']) ? $cliente['direccion'] : ''; ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="tipo_membresia">Tipo de Membresía</label>
                                    <select id="tipo_membresia" name="tipo_membresia" class="form-control" required>
                                        <option value="basica" <?php echo (isset($cliente['tipo_membresia']) && $cliente['tipo_membresia'] == 'basica') ? 'selected' : ''; ?>>Básica</option>
                                        <option value="premium" <?php echo (isset($cliente['tipo_membresia']) && $cliente['tipo_membresia'] == 'premium') ? 'selected' : ''; ?>>Premium</option>
                                        <option value="vip" <?php echo (isset($cliente['tipo_membresia']) && $cliente['tipo_membresia'] == 'vip') ? 'selected' : ''; ?>>VIP</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="estado">Estado</label>
                                    <select id="estado" name="estado" class="form-control" required>
                                        <option value="activo" <?php echo (isset($cliente['estado']) && $cliente['estado'] == 'activo') ? 'selected' : ''; ?>>Activo</option>
                                        <option value="inactivo" <?php echo (isset($cliente['estado']) && $cliente['estado'] == 'inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                                    </select>
                                </div>
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
                                <?php if ($id > 0): ?>
                                <a href="qr_generar.php?id=<?php echo $id; ?>" target="_blank" class="btn btn-secondary">
                                    Ver/Descargar QR
                                </a>
                                <?php else: ?>
                                <p class="text-muted">El QR se generará después de guardar el cliente</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Guardar Cliente</button>
                            <a href="clientes.php" class="btn btn-danger">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <!-- Lista de clientes -->
            <div class="card">
                <div class="card-header">
                    <h3>Lista de Clientes</h3>
                    <a href="clientes.php?action=add" class="btn btn-primary">Agregar Cliente</a>
                </div>
                <div class="card-body">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cédula</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Membresía</th>
                                <th>Actividades</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clientes as $c): ?>
                            <tr>
                                <td>CL-<?php echo str_pad($c['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo $c['cedula']; ?></td>
                                <td><?php echo $c['nombre'] . ' ' . $c['apellido']; ?></td>
                                <td><?php echo $c['email']; ?></td>
                                <td><?php echo $c['telefono']; ?></td>
                                <td><?php echo ucfirst($c['tipo_membresia']); ?></td>
                                <td>
                                    <?php foreach ($c['actividades'] as $actividad): 
                                        $actividadClass = 'tag-' . $actividad;
                                    ?>
                                        <span class="activity-tag <?php echo $actividadClass; ?>"><?php echo ucfirst($actividad); ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $c['estado'] === 'activo' ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo ucfirst($c['estado']); ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <a href="clientes.php?action=edit&id=<?php echo $c['id']; ?>" class="action-btn action-edit">Editar</a>
                                    <a href="acciones/cliente_eliminar.php?id=<?php echo $c['id']; ?>" class="action-btn action-delete" onclick="return confirm('¿Estás seguro de eliminar este cliente?');">Eliminar</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>