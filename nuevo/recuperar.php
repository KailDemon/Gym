<?php
// Verifica si la sesión ya está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Procesar las acciones del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'request':
            // Paso 1: Solicitar recuperación
            $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
            
            if (!$email) {
                $_SESSION['error'] = "Por favor, introduce un correo electrónico válido.";
                header("Location: recuperar.php?step=1");
                exit;
            }
            
            // Simular envío de código (en producción conectarías con tu base de datos)
            $code = sprintf("%06d", mt_rand(1, 999999));
            
            // Guardar en sesión (en producción usarías base de datos)
            $_SESSION['recovery_code'] = $code;
            $_SESSION['recovery_email'] = $email;
            $_SESSION['recovery_expires'] = time() + 1800; // 30 minutos
            
            // En producción aquí enviarías el email real
            $_SESSION['success'] = "Se ha enviado un código de verificación a tu correo electrónico. Código de prueba: $code";
            header("Location: recuperar.php?step=2&email=" . urlencode($email));
            exit;
            
        case 'verify':
            // Paso 2: Verificar código
            $email = $_POST['email'] ?? '';
            $code = $_POST['code'] ?? '';
            
            if (empty($email) || empty($code)) {
                $_SESSION['error'] = "Por favor, completa todos los campos.";
                header("Location: recuperar.php?step=2&email=" . urlencode($email));
                exit;
            }
            
            // Verificar código
            if (!isset($_SESSION['recovery_code']) || 
                $_SESSION['recovery_code'] !== $code ||
                $_SESSION['recovery_email'] !== $email ||
                time() > $_SESSION['recovery_expires']) {
                
                $_SESSION['error'] = "El código de verificación es incorrecto o ha expirado.";
                header("Location: recuperar.php?step=2&email=" . urlencode($email));
                exit;
            }
            
            // Generar token seguro
            $token = bin2hex(random_bytes(32));
            $_SESSION['reset_token'] = $token;
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_expires'] = time() + 1800;
            
            unset($_SESSION['recovery_code'], $_SESSION['recovery_expires']);
            
            header("Location: recuperar.php?step=3&token=" . $token);
            exit;
            
        case 'reset':
            // Paso 3: Restablecer contraseña
            $token = $_POST['token'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if (empty($token) || empty($new_password) || empty($confirm_password)) {
                $_SESSION['error'] = "Por favor, completa todos los campos.";
                header("Location: recuperar.php?step=3&token=" . $token);
                exit;
            }
            
            if ($new_password !== $confirm_password) {
                $_SESSION['error'] = "Las contraseñas no coinciden.";
                header("Location: recuperar.php?step=3&token=" . $token);
                exit;
            }
            
            // Verificar token
            if (!isset($_SESSION['reset_token']) || 
                $_SESSION['reset_token'] !== $token ||
                time() > $_SESSION['reset_expires']) {
                
                $_SESSION['error'] = "El enlace de restablecimiento ha expirado o es inválido.";
                header("Location: recuperar.php?step=1");
                exit;
            }
            
            $email = $_SESSION['reset_email'];
            
            // En producción: actualizar contraseña en la base de datos
            // $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            // actualizarContraseñaEnBD($email, $hashed_password);
            
            // Limpiar sesión
            unset($_SESSION['reset_token'], $_SESSION['reset_email'], $_SESSION['reset_expires']);
            
            // Mensaje específico de confirmación
            $_SESSION['success'] = "✅ Tu contraseña ha sido establecida correctamente. Ahora puedes iniciar sesión con tu nueva contraseña.";
            header("Location: recuperar.php?step=success");
            exit;
    }
}

// Obtener mensajes y determinar paso actual
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
unset($_SESSION['error'], $_SESSION['success']);

$step = isset($_GET['step']) ? $_GET['step'] : 1;
$token = isset($_GET['token']) ? $_GET['token'] : '';

// Cabeceras para evitar caché
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Gimnasio</title>
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

        #register-btn {
            background: linear-gradient(to right, #1DCD9F, #169976);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(218, 27, 96, 0.3);
        }

        #register-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(218, 27, 96, 0.4);
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

        .recovery-container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex: 1;
            padding: 2rem;
        }

        .recovery-form {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
            padding: 2.5rem;
            width: 100%;
            max-width: 450px;
            text-align: center;
            transform: translateY(0);
            transition: transform 0.3s ease;
        }

        .recovery-form:hover {
            transform: translateY(-10px);
        }

        .recovery-logo {
            margin-bottom: 2rem;
        }

        .recovery-logo div {
            font-size: 4rem;
            margin-bottom: 1rem;
            background: linear-gradient(to right, #000000, #222222, #1DCD9F, #169976);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .recovery-logo h2 {
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
            background: linear-gradient(to right, #000000, #222222, #1DCD9F, #169976);
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

        .btn-success {
            background: linear-gradient(to right, #169976, #20c997);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
        }

        .progress-steps::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: #ddd;
            transform: translateY(-50%);
            z-index: 1;
        }

        .progress-bar {
            position: absolute;
            top: 50%;
            left: 0;
            height: 2px;
            background: linear-gradient(to right, #1DCD9F, #169976);
            transform: translateY(-50%);
            z-index: 2;
            transition: width 0.5s ease;
        }

        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #fff;
            border: 2px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            position: relative;
            z-index: 3;
            transition: all 0.3s ease;
        }

        .step.active {
            background: linear-gradient(to right, #1DCD9F, #169976);
            color: white;
            border-color: #1DCD9F;
        }

        .step.completed {
            background: #1DCD9F;
            color: white;
            border-color: #1DCD9F;
        }

        .step-label {
            position: absolute;
            top: 45px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.8rem;
            white-space: nowrap;
            color: #555;
        }

        .step.active .step-label {
            color: #1DCD9F;
            font-weight: bold;
        }

        .recovery-links {
            display: flex;
            justify-content: center;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }

        .recovery-links a {
            color: #1DCD9F;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            padding: 0.5rem 0;
            position: relative;
        }

        .recovery-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(to right, #1DCD9F, #169976);
            transition: width 0.3s ease;
        }

        .recovery-links a:hover {
            color: #169976;
        }

        .recovery-links a:hover::after {
            width: 100%;
        }

        .success-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            color: #169976;
        }

        .success-message {
            font-size: 1.3rem;
            font-weight: 600;
            color: #155724;
            margin-bottom: 2rem;
            line-height: 1.5;
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

            .recovery-form {
                padding: 1.5rem;
            }

            .step-label {
                font-size: 0.7rem;
            }

            .success-message {
                font-size: 1.1rem;
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
                <li><a href="login.php" id="register-btn">Iniciar sesión</a></li>
            </ul>
            <div class="burger">
                <div class="line1"></div>
                <div class="line2"></div>
                <div class="line3"></div>
            </div>
        </nav>
    </header>
    
    <div class="recovery-container">
        <div class="recovery-form">
            <?php if ($step == 'success'): ?>
                <!-- Pantalla de éxito -->
                <div class="recovery-logo">
                    <div class="success-icon">✅</div>
                    <h2>¡Contraseña Establecida!</h2>
                </div>
                
                <div class="success-message">
                    Tu contraseña ha sido establecida correctamente.<br>
                    Ahora puedes iniciar sesión con tu nueva contraseña.
                </div>
                
                <div class="form-group">
                    <a href="login.php" class="btn btn-success">Ir al Inicio de Sesión</a>
                </div>
                
            <?php else: ?>
                <!-- Proceso normal de recuperación -->
                <div class="recovery-logo">
                    <div>🔑</div>
                    <h2>Recuperar Contraseña</h2>
                </div>
                
                <!-- Indicador de progreso -->
                <div class="progress-steps">
                    <div class="progress-bar" style="width: <?php echo ($step == 1) ? '0%' : (($step == 2) ? '50%' : '100%'); ?>"></div>
                    <div class="step <?php echo ($step >= 1) ? 'active' : ''; ?> <?php echo ($step > 1) ? 'completed' : ''; ?>">
                        1
                        <span class="step-label">Email</span>
                    </div>
                    <div class="step <?php echo ($step >= 2) ? 'active' : ''; ?> <?php echo ($step > 2) ? 'completed' : ''; ?>">
                        2
                        <span class="step-label">Código</span>
                    </div>
                    <div class="step <?php echo ($step >= 3) ? 'active' : ''; ?>">
                        3
                        <span class="step-label">Nueva Contraseña</span>
                    </div>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success && $step != 'success'): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <!-- Paso 1: Solicitar recuperación -->
                <?php if ($step == 1): ?>
                    <form method="post" action="">
                        <input type="hidden" name="action" value="request">
                        <div class="form-group">
                            <label for="email">Correo Electrónico</label>
                            <input type="email" id="email" name="email" class="form-control" placeholder="Ingresa tu correo electrónico" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn">Enviar Código de Verificación</button>
                        </div>
                    </form>
                <?php endif; ?>
                
                <!-- Paso 2: Verificar código -->
                <?php if ($step == 2): ?>
                    <form method="post" action="">
                        <input type="hidden" name="action" value="verify">
                        <input type="hidden" name="email" value="<?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>">
                        <div class="form-group">
                            <label for="code">Código de Verificación</label>
                            <input type="text" id="code" name="code" class="form-control" placeholder="Ingresa el código enviado a tu email" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn">Verificar Código</button>
                        </div>
                    </form>
                <?php endif; ?>
                
                <!-- Paso 3: Establecer nueva contraseña -->
                <?php if ($step == 3): ?>
                    <form method="post" action="">
                        <input type="hidden" name="action" value="reset">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        <div class="form-group">
                            <label for="new_password">Nueva Contraseña</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Ingresa tu nueva contraseña" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirmar Contraseña</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirma tu nueva contraseña" required minlength="6">
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn">Establecer Nueva Contraseña</button>
                        </div>
                    </form>
                <?php endif; ?>
                
                <!-- Enlaces adicionales -->
                <div class="recovery-links">
                    <a href="login.php">Volver al inicio de sesión</a>
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
            
            // Validación de contraseña en el paso 3
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (newPassword && confirmPassword) {
                function validatePassword() {
                    if (newPassword.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity('Las contraseñas no coinciden');
                    } else {
                        confirmPassword.setCustomValidity('');
                    }
                }
                
                newPassword.addEventListener('change', validatePassword);
                confirmPassword.addEventListener('keyup', validatePassword);
            }
        });
    </script>
</body>
</html>
