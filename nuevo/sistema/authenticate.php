<?php
session_start();
require 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = $_POST['username'];
    $contrasena = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch();

    if ($user) {
        // Verificar si la cuenta está activa
        if ($user['estado'] !== 'activo') {
            $_SESSION['error'] = "Su cuenta está pendiente de verificación por un administrador";
            header('Location: login.php');
            exit;
        }

        // Verificar contraseña - compatibilidad con ambos métodos
        $passwordValid = false;
        
        // Método 1: Verificar con password_verify (para nuevos registros)
        if (password_verify($contrasena, $user['contrasena'])) {
            $passwordValid = true;
        }
        // Método 2: Verificar con SHA256 (para usuario admin existente)
        elseif (hash('sha256', $contrasena) === $user['contrasena']) {
            $passwordValid = true;
            // Opcional: Actualizar a password_hash para mayor seguridad
            $newHash = password_hash($contrasena, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE usuarios SET contrasena = ? WHERE cedula = ?");
            $updateStmt->execute([$newHash, $user['cedula']]);
        }

        if ($passwordValid) {
            $_SESSION['usuario_id'] = $user['cedula']; // Usar cedula como ID
            $_SESSION['usuario_nombre'] = $user['nombre'];
            $_SESSION['usuario_rol'] = $user['rol'];
            header('Location: index.php');
            exit;
        } else {
            $_SESSION['error'] = "Usuario o contraseña incorrectos";
            header('Location: login.php');
            exit;
        }
    } else {
        $_SESSION['error'] = "Usuario o contraseña incorrectos";
        header('Location: login.php');
        exit;
    }
}
?>