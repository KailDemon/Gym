<?php

require 'validar_sesion.php';  // Reemplaza la validación de sesión
require 'database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}


    
// Obtener estadísticas
$stmtClientes = $pdo->query("SELECT COUNT(*) as total FROM clientes");
$totalClientes = $stmtClientes->fetchColumn();

$stmtUsuarios = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
$totalUsuarios = $stmtUsuarios->fetchColumn();

// Últimos 5 clientes
$stmtUltimos = $pdo->query("SELECT * FROM clientes ORDER BY fecha_registro DESC LIMIT 5");
$ultimosClientes = $stmtUltimos->fetchAll(PDO::FETCH_ASSOC);
$fechaProximoVencimiento = date('Y-m-d', strtotime('+7 days'));
$stmtProximos = $pdo->prepare("SELECT * FROM clientes 
                              WHERE fecha_vencimiento <= ? 
                              AND fecha_vencimiento >= CURDATE()
                              AND estado = 'activo'
                              AND alerta_vencimiento = 1
                              ORDER BY fecha_vencimiento ASC");

$clientesProximos = $stmtProximos->fetchAll(PDO::FETCH_ASSOC);

// Obtener clientes vencidos
$stmtVencidos = $pdo->prepare("SELECT * FROM clientes 
                              WHERE fecha_vencimiento < CURDATE()
                              AND estado = 'activo'
                              ORDER BY fecha_vencimiento ASC");
$stmtVencidos->execute();
$clientesVencidos = $stmtVencidos->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gimnasio</title>
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
                    <li><a href="index.php" class="active"><i>🏠</i> Inicio</a></li>
                    <li><a href="clientes.php"><i>👥</i> Clientes</a></li>
                    <li><a href="asistencia.php"><i>✅</i> Asistencia</a></li>
                    <li><a href="usuarios.php"><i>👤</i> Usuarios</a></li>
                    <li><a href="monitoreo.php"><i>⏱️</i> Monitoreo</a></li> 
                    <li><a href="escanner.php"><i>📷</i> Escanear QR</a></li>
                    <li><a href="logout.php"><i>🔒</i> Cerrar Sesión</a></li>
                </ul>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Inicio</h1>
                <div class="user-info">
                    <span>Bienvenido, <?php echo $_SESSION['usuario_nombre']; ?></span>
                    <a href="logout.php" class="btn btn-danger">Cerrar Sesión</a>
                </div>
            </div>
            <!-- Sección de alertas -->
            <?php if (!empty($clientesProximos) || !empty($clientesVencidos)): ?>
            <div class="card">
                <div class="card-header">
                    <h3>Alertas de Vencimiento</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($clientesVencidos)): ?>
                    <div class="alert alert-danger">
                        <h4>Membresías Vencidas</h4>
                        <?php foreach ($clientesVencidos as $cliente): 
                            $dias = floor((time() - strtotime($cliente['fecha_vencimiento'])) / (60*60*24));
                        ?>
                        <div class="alert-card alert-danger">
                            <strong><?php echo $cliente['nombre'] . ' ' . $cliente['apellido']; ?></strong>
                            <span> - Vencido hace <?php echo $dias; ?> días</span>
                            <p>Membresía: <?php echo ucfirst($cliente['tipo_membresia']); ?> 
                                (Vencimiento: <?php echo date('d/m/Y', strtotime($cliente['fecha_vencimiento'])); ?>)</p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($clientesProximos)): ?>
                    <div class="alert alert-warning">
                        <h4>Membresías Próximas a Vencer</h4>
                        <?php foreach ($clientesProximos as $cliente): 
                            $dias = floor((strtotime($cliente['fecha_vencimiento']) - time()) / (60*60*24));
                        ?>
                        <div class="alert-card alert-warning">
                            <strong><?php echo $cliente['nombre'] . ' ' . $cliente['apellido']; ?></strong>
                            <span class="dias-restantes">- Vence en <?php echo $dias; ?> días</span>
                            <p>Membresía: <?php echo ucfirst($cliente['tipo_membresia']); ?> 
                                (Vencimiento: <?php echo date('d/m/Y', strtotime($cliente['fecha_vencimiento'])); ?>)</p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Estadísticas -->
            <div class="form-row">
                <div class="form-col">
                    <div class="card">
                        <div class="card-header">
                            <h3>Resumen</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="card stat-card">
                                        <div class="card-body">
                                            <h3><?php echo $totalClientes; ?></h3>
                                            <p>Clientes Activos</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="card stat-card">
                                        <div class="card-body">
                                            <h3><?php echo $totalUsuarios; ?></h3>
                                            <p>Usuarios Administrativos</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Últimos clientes -->
            <div class="card">
                <div class="card-header">
                    <h3>Últimos Clientes Registrados</h3>
                    <a href="clientes.php?action=add" class="btn btn-primary">Agregar Cliente</a>
                </div>
                <div class="card-body">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Membresía</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimosClientes as $cliente): ?>
                            <tr>
                                <td>CL-<?php echo str_pad($cliente['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo $cliente['nombre'] . ' ' . $cliente['apellido']; ?></td>
                                <td><?php echo $cliente['email']; ?></td>
                                <td><?php echo $cliente['telefono']; ?></td>
                                <td><?php echo ucfirst($cliente['tipo_membresia']); ?></td>
                                <td class="actions">
                                    <a href="clientes.php?action=edit&id=<?php echo $cliente['id']; ?>" class="action-btn action-edit">Editar</a>
                                    <a href="acciones/cliente_eliminar.php?id=<?php echo $cliente['id']; ?>" class="action-btn action-delete" onclick="return confirm('¿Estás seguro de eliminar este cliente?');">Eliminar</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script>
    // Evitar navegación con botón Atrás
    history.pushState(null, null, location.href);
    window.onpopstate = function() {
        history.go(1);
    };
    
    // Forzar recarga al volver
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
    </script>
</body>
</html>