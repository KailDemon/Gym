<?php
session_start();
require 'database.php';

$mensaje = '';
$tipoMensaje = '';
$redireccionar = false;

// Procesar registro
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $email = trim($_POST['email']);
    $cedula = trim($_POST['cedula']);
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $contrasena = $_POST['contrasena'];
    $confirmar_contrasena = $_POST['confirmar_contrasena'];
    
    // Validaciones básicas
    if (empty($nombre) || empty($apellido) || empty($email) || empty($cedula) || empty($fecha_nacimiento) || empty($contrasena)) {
        $mensaje = 'Todos los campos son obligatorios';
        $tipoMensaje = 'danger';
    } elseif (!is_numeric($cedula)) {
        $mensaje = 'La cédula debe contener solo números';
        $tipoMensaje = 'danger';
    } elseif ($contrasena !== $confirmar_contrasena) {
        $mensaje = 'Las contraseñas no coinciden';
        $tipoMensaje = 'danger';
    } elseif (strlen($contrasena) < 6) {
        $mensaje = 'La contraseña debe tener al menos 6 caracteres';
        $tipoMensaje = 'danger';
    } else {
        try {
            // Verificar si la cédula o email ya existen
            $stmt = $pdo->prepare("SELECT cedula FROM usuarios WHERE cedula = ? OR email = ?");
            $stmt->execute([$cedula, $email]);
            $existe = $stmt->fetch();
            
            if ($existe) {
                $mensaje = 'La cédula o email ya está en uso';
                $tipoMensaje = 'danger';
            } else {
                // Insertar nuevo usuario
                $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);
                
                // CORRECCIÓN: Incluir el campo apellido en la consulta
                $sql = "INSERT INTO usuarios (cedula, nombre, apellido, email, usuario, contrasena, fecha_nacimiento, rol, estado) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'cliente', 'pendiente')";
                $stmt = $pdo->prepare($sql);
                $resultado = $stmt->execute([$cedula, $nombre, $apellido, $email, $cedula, $contrasena_hash, $fecha_nacimiento]);
                
                if ($resultado) {
                    $mensaje = 'Registro exitoso. Un administrador verificará su cuenta lo antes posible. Serás redirigido al inicio de sesión en 3 segundos.';
                    $tipoMensaje = 'success';
                    $redireccionar = true;
                    
                    // Limpiar campos después de registro exitoso
                    $nombre = $apellido = $email = $cedula = $fecha_nacimiento = '';
                } else {
                    $mensaje = 'Error al registrar el usuario. Inténtalo de nuevo.';
                    $tipoMensaje = 'danger';
                }
            }
        } catch (PDOException $e) {
            $mensaje = 'Error en el sistema: ' . $e->getMessage();
            $tipoMensaje = 'danger';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Gimnasio</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #000000, #222222, #1DCD9F);
            background-size: 400% 400%;
            animation: gradientBG 7.5s ease infinite;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: #333;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        header {
            background: rgba(0, 0, 0, 0.85);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem 2rem;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 1px;
            background: linear-gradient(to right, #1DCD9F, #169976);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            transition: all 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.3);
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 1.5rem;
        }

        .nav-links li a {
            color: #fff;
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 500;
            position: relative;
            padding: 0.5rem 0;
            transition: all 0.3s ease;
        }

        .nav-links li a:hover {
            color: #ff8a00;
        }

        .nav-links li a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(to right, #1DCD9F, #169976);
            transition: width 0.3s ease;
        }

        .nav-links li a:hover::after {
            width: 100%;
        }

        #login-btn {
            background: linear-gradient(to right, #1DCD9F, #169976);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(218, 27, 96, 0.3);
        }

        #login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(218, 27, 96, 0.4);
            color: #fff;
        }

        #login-btn::after {
            display: none;
        }

        .burger {
            display: none;
            cursor: pointer;
        }

        .burger div {
            width: 25px;
            height: 3px;
            background: #fff;
            margin: 5px;
            transition: all 0.3s ease;
        }

        .register-container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex: 1;
            padding: 2rem;
        }

        .register-form {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
            padding: 2.5rem;
            width: 100%;
            max-width: 500px;
            text-align: center;
            transform: translateY(0);
            transition: transform 0.3s ease;
        }

        .register-form:hover {
            transform: translateY(-10px);
        }

        .register-logo {
            margin-bottom: 2rem;
        }

        .register-logo div {
            font-size: 4rem;
            margin-bottom: 1rem;
            background: linear-gradient(to right,  #000000, #222222, #1DCD9F, #169976);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .register-logo h2 {
            font-size: 2.2rem;
            color: #333;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
        }

        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #ff8a00;
            box-shadow: 0 0 0 3px rgba(255, 138, 0, 0.2);
            outline: none;
        }

        .btn {
            display: inline-block;
            padding: 0.8rem 2rem;
            background: linear-gradient(to right, #000000  , #222222, #1DCD9F, #169976);
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            box-shadow: 0 4px 15px rgba(218, 27, 96, 0.3);
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(218, 27, 96, 0.4);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
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
            gap: 1rem;
        }

        .form-col {
            flex: 1;
        }

        .register-links {
            display: flex;
            justify-content: center;
            margin-top: 1.5rem;
        }

        .register-links a {
            color: #1DCD9F;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            padding: 0.5rem 0;
            position: relative;
        }

        .register-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(to right, #1DCD9F, #169976);
            transition: width 0.3s ease;
        }

        .register-links a:hover {
            color: #169976;
        }

        .register-links a:hover::after {
            width: 100%;
        }

        .info-box {
            background-color: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .info-box p {
            margin: 0;
            color: #004085;
            font-size: 0.9rem;
        }

        .info-icon {
            display: inline-block;
            margin-right: 8px;
            font-size: 1.1rem;
        }

        @media screen and (max-width: 768px) {
            .nav-links {
                position: absolute;
                right: 0;
                top: 70px;
                background: rgba(0, 0, 0, 0.9);
                height: calc(100vh - 70px);
                width: 60%;
                flex-direction: column;
                align-items: center;
                padding: 2rem 0;
                transform: translateX(100%);
                transition: transform 0.5s ease-in;
            }

            .nav-links.active {
                transform: translateX(0);
            }

            .burger {
                display: block;
            }

            .register-form {
                padding: 1.5rem;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Polideportivo</div>
            <ul class="nav-links">
                <li><a href="../index.html">Inicio</a></li>
                <li><a href="../gimnasio.html">Instalaciones</a></li>
                <li><a href="../servicios.html">Servicios</a></li>
                <li><a href="../DeporteC.html">Actividades</a></li>
                <li><a href="../ubicacion.html">Contacto</a></li>
                <li><a href="login.php" id="login-btn">Iniciar sesión</a></li>
            </ul>
            <div class="burger">
                <div class="line1"></div>
                <div class="line2"></div>
                <div class="line3"></div>
            </div>
        </nav>
    </header>
    
    <div class="register-container">
        <div class="register-form">
            <div class="register-logo">
                <div>💪</div>
                <h2>Crear Cuenta</h2>
            </div>
            
            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipoMensaje; ?>" id="mensaje-registro">
                    <?php echo $mensaje; ?>
                    <?php if ($redireccionar): ?>
                        <div id="contador-redireccion" style="margin-top: 10px; font-weight: bold;">Redirigiendo en <span id="segundos">3</span> segundos...</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$redireccionar): ?>
            
            <div class="info-box">
                <p><span class="info-icon">ℹ️</span> <strong>Nota:</strong> Su cédula será utilizada como nombre de usuario para iniciar sesión.</p>
            </div>
            
            <form method="post" action="registro.php">
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="nombre">Nombre</label>
                            <input type="text" id="nombre" name="nombre" class="form-control" 
                                   value="<?php echo isset($nombre) ? htmlspecialchars($nombre) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="apellido">Apellido</label>
                            <input type="text" id="apellido" name="apellido" class="form-control" 
                                   value="<?php echo isset($apellido) ? htmlspecialchars($apellido) : ''; ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="cedula">Cédula</label>
                            <input type="text" id="cedula" name="cedula" class="form-control" 
                                   value="<?php echo isset($cedula) ? htmlspecialchars($cedula) : ''; ?>" 
                                   pattern="[0-9]+" title="La cédula debe contener solo números" required>
                            <small style="color: #666; font-size: 0.8rem;">Solo números, sin puntos ni guiones. Será su usuario.</small>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                            <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" class="form-control" 
                                   value="<?php echo isset($fecha_nacimiento) ? htmlspecialchars($fecha_nacimiento) : ''; ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="contrasena">Contraseña</label>
                            <input type="password" id="contrasena" name="contrasena" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="confirmar_contrasena">Confirmar Contraseña</label>
                            <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" class="form-control" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn">Registrarse</button>
                </div>
            </form>
            
            <div class="register-links">
                <a href="login.php">¿Ya tienes una cuenta? Inicia sesión</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const burger = document.querySelector('.burger');
            const navLinks = document.querySelector('.nav-links');
            
            burger.addEventListener('click', () => {
                navLinks.classList.toggle('active');
                burger.classList.toggle('toggle');
            });
            
            <?php if ($redireccionar): ?>
            let segundos = 3;
            const contador = document.getElementById('segundos');
            const intervalo = setInterval(() => {
                segundos--;
                contador.textContent = segundos;
                
                if (segundos <= 0) {
                    clearInterval(intervalo);
                    window.location.href = 'login.php';
                }
            }, 1000);
            <?php endif; ?>
        });
    </script>
</body>
</html>