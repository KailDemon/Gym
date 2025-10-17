<?php
session_start();
require 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = $_POST['username'];
    $contrasena = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch();

    // Cambiar a verificación con SHA2
    if ($user && hash('sha256', $contrasena) === $user['contrasena']) {
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['usuario_nombre'] = $user['nombre'];
        $_SESSION['usuario_rol'] = $user['rol'];
        header('Location: index.php');
        exit;
    } else {
        $_SESSION['error'] = "Usuario o contraseña incorrectos";
        header('Location: login.php');
        exit;
    }
}
?>