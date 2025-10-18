<?php
session_start();
require 'database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Solo administradores pueden gestionar usuarios
if ($_SESSION['usuario_rol'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$cedula = isset($_GET['cedula']) ? $_GET['cedula'] : 0;

// Mensajes
$mensaje = isset($_SESSION['mensaje']) ? $_SESSION['mensaje'] : '';
$tipoMensaje = isset($_SESSION['tipo_mensaje']) ? $_SESSION['tipo_mensaje'] : '';
unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);

// Obtener datos para editar
$usuario = [];
if ($action == 'edit' && !empty($cedula)) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE cedula = ?");
    $stmt->execute([$cedula]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        $action = 'list';
    }
}

// Listar usuarios
if ($action == 'list') {
    $stmt = $pdo->query("SELECT * FROM usuarios ORDER BY fecha_creacion DESC");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Gimnasio</title>
    <link rel="stylesheet" href="css/diseño.css">
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
                    <li><a href="index.php"><i>🏠</i> Inicio</a></li>
                    <li><a href="clientes.php"><i>👥</i> Clientes</a></li>
                    <li><a href="asistencia.php"><i>✅</i> Asistencia</a></li>
                    <li><a href="monitoreo.php"><i>⏱️</i> Monitoreo</a></li> 
                    <li><a href="usuarios.php" class="active"><i>👤</i> Usuarios</a></li>
                    <li><a href="escanner.php"><i>📷</i> Escanear QR</a></li>
                    <li><a href="logout.php"><i>🔒</i> Cerrar Sesión</a></li>
                </ul>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1><?php echo $action == 'add' ? 'Agregar Usuario' : ($action == 'edit' ? 'Editar Usuario' : 'Usuarios'); ?></h1>
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
            <!-- Formulario de usuario -->
            <div class="card">
                <div class="card-header">
                    <h3><?php echo $action == 'add' ? 'Agregar Nuevo Usuario' : 'Editar Usuario'; ?></h3>
                </div>
                <div class="card-body">
                    <form method="post" action="acciones/usuario_guardar.php">
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="cedula">Cédula</label>
                                    <input type="text" id="cedula" name="cedula" class="form-control" 
                                           value="<?php echo isset($usuario['cedula']) ? $usuario['cedula'] : ''; ?>" 
                                           <?php echo $action == 'edit' ? 'readonly' : 'required'; ?>>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="nombre">Nombre Completo</label>
                                    <input type="text" id="nombre" name="nombre" class="form-control" value="<?php echo isset($usuario['nombre']) ? $usuario['nombre'] : ''; ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" class="form-control" value="<?php echo isset($usuario['email']) ? $usuario['email'] : ''; ?>" required>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="usuario">Nombre de Usuario</label>
                                    <input type="text" id="usuario" name="usuario" class="form-control" value="<?php echo isset($usuario['usuario']) ? $usuario['usuario'] : ''; ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <?php if ($action == 'add'): ?>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="contrasena">Contraseña</label>
                                    <input type="password" id="contrasena" name="contrasena" class="form-control" required>
                                </div>
                            </div>
                            <?php endif; ?>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="rol">Rol</label>
                                    <select id="rol" name="rol" class="form-control" required>
                                        <option value="admin" <?php echo (isset($usuario['rol']) && $usuario['rol'] == 'admin') ? 'selected' : ''; ?>>Administrador</option>
                                        <option value="staff" <?php echo (isset($usuario['rol']) && $usuario['rol'] == 'staff') ? 'selected' : ''; ?>>Staff</option>
                                        <option value="cliente" <?php echo (isset($usuario['rol']) && $usuario['rol'] == 'cliente') ? 'selected' : ''; ?>>Cliente</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Guardar Usuario</button>
                            <a href="usuarios.php" class="btn btn-danger">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <!-- Lista de usuarios -->
            <div class="card">
                <div class="card-header">
                    <h3>Lista de Usuarios</h3>
                    <a href="usuarios.php?action=add" class="btn btn-primary">Agregar Usuario</a>
                </div>
                <div class="card-body">
                    <table>
                        <thead>
                            <tr>
                                <th>Cédula</th>
                                <th>Nombre</th>
                                <th>Usuario</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td><?php echo $u['cedula']; ?></td>
                                <td><?php echo $u['nombre']; ?></td>
                                <td><?php echo $u['usuario']; ?></td>
                                <td><?php echo $u['email']; ?></td>
                                <td><?php echo ucfirst($u['rol']); ?></td>
                                <td>
                                    <span class="estado-badge estado-<?php echo $u['estado']; ?>">
                                        <?php echo ucfirst($u['estado']); ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <a href="usuarios.php?action=edit&cedula=<?php echo $u['cedula']; ?>" class="action-btn action-edit">Editar</a>
                                    
                                    <?php if ($_SESSION['usuario_rol'] === 'admin' && $u['cedula'] != $_SESSION['usuario_id']): 
                                        $estado_actual = $u['estado'];
                                        $proximo_estado = '';
                                        
                                        switch ($estado_actual) {
                                            case 'activo': $proximo_estado = 'inactivo'; break;
                                            case 'inactivo': $proximo_estado = 'activo'; break;
                                            case 'pendiente': $proximo_estado = 'activo'; break;
                                            default: $proximo_estado = 'activo';
                                        }
                                    ?>
                                        <a href="acciones/usuario_cambiar_estado.php?cedula=<?php echo $u['cedula']; ?>" 
                                           class="action-btn action-state" 
                                           title="Cambiar de <?php echo $estado_actual; ?> a <?php echo $proximo_estado; ?>"
                                           onclick="return confirm('¿Cambiar estado de <?php echo $u['nombre']; ?> de <?php echo $estado_actual; ?> a <?php echo $proximo_estado; ?>?');">
                                            Cambiar Estado
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($u['cedula'] != $_SESSION['usuario_id']): ?>
                                        <a href="acciones/usuario_eliminar.php?cedula=<?php echo $u['cedula']; ?>" 
                                           class="action-btn action-delete" 
                                           onclick="return confirm('¿Estás seguro de eliminar este usuario?');">
                                            Eliminar
                                        </a>
                                    <?php endif; ?>
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

    <style>
        .estado-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .estado-activo {
            background-color: #d4edda;
            color: #155724;
        }
        .estado-pendiente {
            background-color: #fff3cd;
            color: #856404;
        }
        .estado-inactivo {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .action-btn {
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
            margin: 0.1rem;
            transition: all 0.3s ease;
        }
        
        .action-edit {
            background: #2196f3;
            color: white;
        }
        
        .action-edit:hover {
            background: #1976d2;
        }
        
        .action-state {
            background: #ff9800;
            color: white;
        }
        
        .action-state:hover {
            background: #f57c00;
        }
        
        .action-delete {
            background: #f44336;
            color: white;
        }
        
        .action-delete:hover {
            background: #d32f2f;
        }
        
        .actions {
            white-space: nowrap;
        }
    </style>
</body>
</html>