<?php
session_start();
require '../database.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

// Usar cedula como identificador en lugar de id
$cedula = isset($_POST['cedula']) ? $_POST['cedula'] : (isset($_POST['id']) ? $_POST['id'] : 0);
$nombre = trim($_POST['nombre']);
$email = trim($_POST['email']);
$usuario = trim($_POST['usuario']);
$rol = $_POST['rol'];

// Para contraseña (solo si es nuevo o se cambia)
$contrasena = isset($_POST['contrasena']) ? $_POST['contrasena'] : '';

try {
    if (!empty($cedula)) {
        // Verificar si es actualización o nuevo registro
        $stmt = $pdo->prepare("SELECT cedula FROM usuarios WHERE cedula = ?");
        $stmt->execute([$cedula]);
        $usuario_existente = $stmt->fetch();
        
        if ($usuario_existente) {
            // Actualizar usuario existente
            if (!empty($contrasena)) {
                $hashedPassword = password_hash($contrasena, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ?, usuario = ?, rol = ?, contrasena = ? WHERE cedula = ?");
                $result = $stmt->execute([$nombre, $email, $usuario, $rol, $hashedPassword, $cedula]);
            } else {
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ?, usuario = ?, rol = ? WHERE cedula = ?");
                $result = $stmt->execute([$nombre, $email, $usuario, $rol, $cedula]);
            }
        } else {
            // Crear nuevo usuario - necesitamos contraseña
            if (empty($contrasena)) {
                $_SESSION['mensaje'] = 'La contraseña es requerida para nuevos usuarios';
                $_SESSION['tipo_mensaje'] = 'danger';
                header('Location: ../usuarios.php');
                exit;
            }
            
            $hashedPassword = password_hash($contrasena, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (cedula, nombre, email, usuario, contrasena, rol, estado) VALUES (?, ?, ?, ?, ?, ?, 'activo')");
            $result = $stmt->execute([$cedula, $nombre, $email, $usuario, $hashedPassword, $rol]);
        }

        if ($result) {
            $_SESSION['mensaje'] = $usuario_existente ? 'Usuario actualizado correctamente' : 'Usuario creado correctamente';
            $_SESSION['tipo_mensaje'] = 'success';
        } else {
            $_SESSION['mensaje'] = 'Error al guardar el usuario';
            $_SESSION['tipo_mensaje'] = 'danger';
        }
    } else {
        $_SESSION['mensaje'] = 'La cédula es requerida';
        $_SESSION['tipo_mensaje'] = 'danger';
    }
} catch (PDOException $e) {
    // Verificar si es error de duplicado
    if ($e->getCode() == 23000) { // Código de error para duplicados
        $_SESSION['mensaje'] = 'Error: La cédula, usuario o email ya existen';
    } else {
        $_SESSION['mensaje'] = 'Error en la base de datos: ' . $e->getMessage();
    }
    $_SESSION['tipo_mensaje'] = 'danger';
}

header('Location: ../usuarios.php');
exit;
?>