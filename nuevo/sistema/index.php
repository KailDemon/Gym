<?php
session_start();
require 'database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Obtener estadísticas
$stmtClientes = $pdo->query("SELECT COUNT(*) as total FROM vista_clientes_completa WHERE estado = 'activo'");
$totalClientes = $stmtClientes->fetchColumn();

$stmtUsuarios = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE rol IN ('admin', 'staff')");
$totalUsuarios = $stmtUsuarios->fetchColumn();

// Últimos 5 clientes
$stmtUltimos = $pdo->query("SELECT * FROM vista_clientes_completa ORDER BY fecha_registro DESC LIMIT 5");
$ultimosClientes = $stmtUltimos->fetchAll(PDO::FETCH_ASSOC);

// Clientes con membresías próximas a vencer (próximos 7 días)
$fechaProximoVencimiento = date('Y-m-d', strtotime('+7 days'));
$stmtProximos = $pdo->prepare("SELECT * FROM vista_clientes_completa 
                              WHERE fecha_vencimiento <= ? 
                              AND fecha_vencimiento >= CURDATE()
                              AND estado = 'activo'
                              ORDER BY fecha_vencimiento ASC");
$stmtProximos->execute([$fechaProximoVencimiento]);
$clientesProximos = $stmtProximos->fetchAll(PDO::FETCH_ASSOC);

// Obtener clientes vencidos
$stmtVencidos = $pdo->query("SELECT * FROM vista_clientes_completa 
                            WHERE fecha_vencimiento < CURDATE()
                            AND estado = 'activo'
                            ORDER BY fecha_vencimiento ASC");
$clientesVencidos = $stmtVencidos->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas de membresías
$stmtMembresias = $pdo->query("SELECT tipo_membresia, COUNT(*) as total FROM clientes GROUP BY tipo_membresia");
$membresias = $stmtMembresias->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gimnasio - Dashboard</title>
    <link rel="stylesheet" href="css/diseño.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid #1DCD9F;
        }
        
        .stat-card h3 {
            font-size: 2.5rem;
            margin: 0;
            color: #1DCD9F;
            font-weight: bold;
        }
        
        .stat-card p {
            margin: 0.5rem 0 0 0;
            color: #666;
            font-weight: 600;
        }
        
        .membresia-stats {
            display: flex;
            justify-content: space-around;
            margin-top: 1rem;
        }
        
        .membresia-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
        }
        
        .membresia-basica { background: #e3f2fd; color: #1976d2; }
        .membresia-premium { background: #fff3e0; color: #f57c00; }
        .membresia-vip { background: #f3e5f5; color: #7b1fa2; }
        
        .alert-card {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .alert-danger .alert-card { border-left-color: #f44336; background: #ffebee; }
        .alert-warning .alert-card { border-left-color: #ff9800; background: #fff3e0; }
        
        .dias-restantes {
            font-weight: bold;
            color: #ff9800;
        }
        
        .table-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-btn {
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .action-edit {
            background: #2196f3;
            color: white;
        }
        
        .action-delete {
            background: #f44336;
            color: white;
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
                <h1>Dashboard</h1>
                <div class="user-info">
                    <span>Bienvenido, <?php echo $_SESSION['usuario_nombre']; ?> (<?php echo $_SESSION['usuario_rol']; ?>)</span>
                    <a href="logout.php" class="btn btn-danger">Cerrar Sesión</a>
                </div>
            </div>
            
            <!-- Sección de alertas -->
            <?php if (!empty($clientesProximos) || !empty($clientesVencidos)): ?>
            <div class="card">
                <div class="card-header">
                    <h3>🔔 Alertas de Vencimiento</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($clientesVencidos)): ?>
                    <div class="alert alert-danger">
                        <h4>⏰ Membresías Vencidas</h4>
                        <?php foreach ($clientesVencidos as $cliente): 
                            $dias = floor((time() - strtotime($cliente['fecha_vencimiento'])) / (60*60*24));
                        ?>
                        <div class="alert-card alert-danger">
                            <strong><?php echo $cliente['nombre_completo']; ?></strong>
                            <span> - Vencido hace <?php echo $dias; ?> días</span>
                            <p>Membresía: <span class="membresia-badge membresia-<?php echo $cliente['tipo_membresia']; ?>">
                                <?php echo ucfirst($cliente['tipo_membresia']); ?>
                            </span> 
                            (Vencimiento: <?php echo date('d/m/Y', strtotime($cliente['fecha_vencimiento'])); ?>)</p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($clientesProximos)): ?>
                    <div class="alert alert-warning">
                        <h4>⚠️ Membresías Próximas a Vencer</h4>
                        <?php foreach ($clientesProximos as $cliente): 
                            $dias = floor((strtotime($cliente['fecha_vencimiento']) - time()) / (60*60*24));
                        ?>
                        <div class="alert-card alert-warning">
                            <strong><?php echo $cliente['nombre_completo']; ?></strong>
                            <span class="dias-restantes">- Vence en <?php echo $dias; ?> días</span>
                            <p>Membresía: <span class="membresia-badge membresia-<?php echo $cliente['tipo_membresia']; ?>">
                                <?php echo ucfirst($cliente['tipo_membresia']); ?>
                            </span>
                            (Vencimiento: <?php echo date('d/m/Y', strtotime($cliente['fecha_vencimiento'])); ?>)</p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Estadísticas -->
            <div class="card">
                <div class="card-header">
                    <h3>📊 Resumen General</h3>
                </div>
                <div class="card-body">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <h3><?php echo $totalClientes; ?></h3>
                            <p>Clientes Activos</p>
                        </div>
                        <div class="stat-card">
                            <h3><?php echo $totalUsuarios; ?></h3>
                            <p>Usuarios del Sistema</p>
                        </div>
                        <div class="stat-card">
                            <h3><?php echo count($clientesProximos); ?></h3>
                            <p>Membresías por Vencer</p>
                        </div>
                        <div class="stat-card">
                            <h3><?php echo count($clientesVencidos); ?></h3>
                            <p>Membresías Vencidas</p>
                        </div>
                    </div>
                    
                    <!-- Distribución de membresías -->
                    <?php if (!empty($membresias)): ?>
                    <div class="membresia-stats">
                        <?php foreach ($membresias as $membresia): ?>
                        <span class="membresia-badge membresia-<?php echo $membresia['tipo_membresia']; ?>">
                            <?php echo ucfirst($membresia['tipo_membresia']); ?>: <?php echo $membresia['total']; ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Últimos clientes registrados -->
            <div class="card">
                <div class="card-header">
                    <h3>👥 Últimos Clientes Registrados</h3>
                    <a href="clientes.php?action=add" class="btn btn-primary">Agregar Cliente</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($ultimosClientes)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Cédula</th>
                                <th>Nombre Completo</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Membresía</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimosClientes as $cliente): ?>
                            <tr>
                                <td><?php echo $cliente['cedula']; ?></td>
                                <td><?php echo $cliente['nombre_completo']; ?></td>
                                <td><?php echo $cliente['email']; ?></td>
                                <td><?php echo $cliente['telefono'] ?: 'N/A'; ?></td>
                                <td>
                                    <span class="membresia-badge membresia-<?php echo $cliente['tipo_membresia']; ?>">
                                        <?php echo ucfirst($cliente['tipo_membresia']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="clientes.php?action=edit&cedula=<?php echo $cliente['cedula']; ?>" class="action-btn action-edit">Editar</a>
                                        <a href="acciones/cliente_eliminar.php?cedula=<?php echo $cliente['cedula']; ?>" class="action-btn action-delete" onclick="return confirm('¿Estás seguro de eliminar este cliente?');">Eliminar</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p>No hay clientes registrados.</p>
                    <?php endif; ?>
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

    // Auto-ocultar alertas después de 10 segundos
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.opacity = '0.7';
        });
    }, 10000);
    </script>
</body>
</html>