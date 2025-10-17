<?php
session_start();
require 'database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escanear QR - Gimnasio</title>
    <link rel="stylesheet" href="css/diseño.css">
    <script src="https://unpkg.com/html5-qrcode"></script>
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
                    <li><a href="usuarios.php"><i>👤</i> Usuarios</a></li>
                    <li><a href="monitoreo.php"><i>⏱️</i> Monitoreo</a></li> 
                    <li><a href="escanner.php" class="active"><i>📷</i> Escanear QR</a></li>
                    <li><a href="logout.php"><i>🔒</i> Cerrar Sesión</a></li>
                </ul>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Escanear QR de Asistencia</h1>
                <div class="user-info">
                    <span>Bienvenido, <?php echo $_SESSION['usuario_nombre']; ?></span>
                    <a href="logout.php" class="btn btn-danger">Cerrar Sesión</a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div id="qr-reader" style="width: 500px; margin: 0 auto;"></div>
                    <div id="qr-result" style="text-align: center; margin-top: 20px;"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function onScanSuccess(qrCodeMessage) {
            // Detener el escáner
            html5QrcodeScanner.clear().then(_ => {
                // Enviar el código QR al servidor
                fetch('acciones/registrar_asistencia.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ codigo_qr: qrCodeMessage })
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('qr-result').innerHTML = 
                        data.success ? 
                        `<div class="alert alert-success">${data.message}</div>` :
                        `<div class="alert alert-danger">${data.message}</div>`;
                    
                    // Reiniciar el escáner después de 3 segundos
                    setTimeout(() => {
                        document.getElementById('qr-result').innerHTML = '';
                        startScanner();
                    }, 3000);
                })
                .catch(error => {
                    document.getElementById('qr-result').innerHTML = 
                        '<div class="alert alert-danger">Error al conectar con el servidor</div>';
                    setTimeout(startScanner, 3000);
                });
            }).catch(error => console.error('Error al detener el escáner', error));
        }

        function onScanError(errorMessage) {
            // Manejar errores de escaneo
        }

        let html5QrcodeScanner;
        function startScanner() {
            html5QrcodeScanner = new Html5QrcodeScanner(
                "qr-reader", 
                { 
                    fps: 10, 
                    qrbox: 250,
                    formatsToSupport: [ Html5QrcodeSupportedFormats.QR_CODE ]
                },
                /* verbose= */ false
            );
            html5QrcodeScanner.render(onScanSuccess, onScanError);
        }

        // Iniciar el escáner al cargar la página
        window.onload = startScanner;
    </script>
</body>
</html>