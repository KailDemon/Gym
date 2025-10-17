<?php
session_start();
require '../database.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$nombre = $_POST['nombre'];
$email = $_POST['email'];
$usuario = $_POST['usuario'];
$rol = $_POST['rol'];

// Para contraseña (solo si es nuevo o se cambia)
$contrasena = isset($_POST['contrasena']) ? $_POST['contrasena'] : '';

if ($id > 0) {
    // Actualizar usuario existente
    if (!empty($contrasena)) {
        $hashedPassword = hash('sha256', $contrasena);
        $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ?, usuario = ?, rol = ?, contrasena = ? WHERE id = ?");
        $result = $stmt->execute([$nombre, $email, $usuario, $rol, $hashedPassword, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ?, usuario = ?, rol = ? WHERE id = ?");
        $result = $stmt->execute([$nombre, $email, $usuario, $rol, $id]);
    }
} else {
    // Crear nuevo usuario
    $hashedPassword = hash('sha256', $contrasena);
    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, usuario, contrasena, rol, fecha_creacion) VALUES (?, ?, ?, ?, ?, NOW())");
    $result = $stmt->execute([$nombre, $email, $usuario, $hashedPassword, $rol]);
}

if ($result) {
    $_SESSION['mensaje'] = $id > 0 ? 'Usuario actualizado correctamente' : 'Usuario creado correctamente';
    $_SESSION['tipo_mensaje'] = 'success';
} else {
    $_SESSION['mensaje'] = 'Error al guardar el usuario';
    $_SESSION['tipo_mensaje'] = 'danger';
}

header('Location: ../usuarios.php');
exit;
?>