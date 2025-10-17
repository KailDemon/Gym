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
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Mensajes
$mensaje = isset($_SESSION['mensaje']) ? $_SESSION['mensaje'] : '';
$tipoMensaje = isset($_SESSION['tipo_mensaje']) ? $_SESSION['tipo_mensaje'] : '';
unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);

// Obtener datos para editar
$usuario = [];
if ($action == 'edit' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
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
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="nombre">Nombre Completo</label>
                                    <input type="text" id="nombre" name="nombre" class="form-control" value="<?php echo isset($usuario['nombre']) ? $usuario['nombre'] : ''; ?>" required>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" class="form-control" value="<?php echo isset($usuario['email']) ? $usuario['email'] : ''; ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="usuario">Nombre de Usuario</label>
                                    <input type="text" id="usuario" name="usuario" class="form-control" value="<?php echo isset($usuario['usuario']) ? $usuario['usuario'] : ''; ?>" required>
                                </div>
                            </div>
                            <?php if ($action == 'add'): ?>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="contrasena">Contraseña</label>
                                    <input type="password" id="contrasena" name="contrasena" class="form-control" required>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="rol">Rol</label>
                                    <select id="rol" name="rol" class="form-control" required>
                                        <option value="admin" <?php echo (isset($usuario['rol']) && $usuario['rol'] == 'admin') ? 'selected' : ''; ?>>Administrador</option>
                                        <option value="staff" <?php echo (isset($usuario['rol']) && $usuario['rol'] == 'staff') ? 'selected' : ''; ?>>Staff</option>
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
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Usuario</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td>US-<?php echo str_pad($u['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo $u['nombre']; ?></td>
                                <td><?php echo $u['usuario']; ?></td>
                                <td><?php echo $u['email']; ?></td>
                                <td><?php echo ucfirst($u['rol']); ?></td>
                                <td class="actions">
                                    <a href="usuarios.php?action=edit&id=<?php echo $u['id']; ?>" class="action-btn action-edit">Editar</a>
                                    <?php if ($u['id'] != $_SESSION['usuario_id']): ?>
                                    <a href="acciones/usuario_eliminar.php?id=<?php echo $u['id']; ?>" class="action-btn action-delete" onclick="return confirm('¿Estás seguro de eliminar este usuario?');">Eliminar</a>
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
</body>
</html>