<?php
// Verifica si la sesión ya está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']);
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
    <title>Login - Gimnasio</title>
    <style>
        /* RESET Y ESTILOS BASE */
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

        /* HEADER Y NAVEGACIÓN */
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
            color: #fff;
        }

        #register-btn::after {
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

        /* CONTENEDOR LOGIN */
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex: 1;
            padding: 2rem;
        }

        .login-form {
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

        .login-form:hover {
            transform: translateY(-10px);
        }

        .login-logo {
            margin-bottom: 2rem;
        }

        .login-logo div {
            font-size: 4rem;
            margin-bottom: 1rem;
            background: linear-gradient(to right,  #000000, #222222, #1DCD9F, #169976);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .login-logo h2 {
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
            background-color: #f8d7da;
            color: #721c24;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #f5c6cb;
        }

        /* ENLACES ADICIONALES */
        .login-links {
            display: flex;
            justify-content: space-between;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }

        .login-links a {
            color: #1DCD9F;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            padding: 0.5rem 0;
            position: relative;
        }

        .login-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(to right, #1DCD9F, #169976);
            transition: width 0.3s ease;
        }

        .login-links a:hover {
            color: #169976;
        }

        .login-links a:hover::after {
            width: 100%;
        }

        /* RESPONSIVE */
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

            .login-form {
                padding: 1.5rem;
            }

            .login-links {
                flex-direction: column;
                gap: 0.5rem;
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
                <li><a href="sistema/login.php" id="register-btn">Iniciar sesión</a></li>
            </ul>
            <div class="burger">
                <div class="line1"></div>
                <div class="line2"></div>
                <div class="line3"></div>
            </div>
        </nav>
    </header>
    
    <div class="login-container">
        <div class="login-form">
            <div class="login-logo">
                <div>💪</div>
                <h2>Gimnasio</h2>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="post" action="authenticate.php">
                <div class="form-group">
                    <label for="username">Usuario</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
                </div>
            </form>
            
            <!-- Enlaces adicionales -->
            <div class="login-links">
                <a href="registro.php">Crear una cuenta</a>
                <a href="recuperar.php">¿Olvidaste tu contraseña?</a>
            </div>
        </div>
    </div>

    <script>
        // JavaScript para el menú hamburguesa
        document.addEventListener('DOMContentLoaded', function() {
            const burger = document.querySelector('.burger');
            const navLinks = document.querySelector('.nav-links');
            
            burger.addEventListener('click', () => {
                navLinks.classList.toggle('active');
                
                // Animación para el icono hamburguesa
                burger.classList.toggle('toggle');
            });
        });
    </script>
</body>
</html>