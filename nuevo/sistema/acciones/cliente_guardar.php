<?php
session_start();
require '../database.php';

if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

// Recoger datos del formulario
$cedula = $_POST['cedula'];
$nombre = trim($_POST['nombre']);
$apellido = trim($_POST['apellido']);
$email = trim($_POST['email']);
$telefono = trim($_POST['telefono']);
$direccion = trim($_POST['direccion']);
$fecha_nacimiento = $_POST['fecha_nacimiento'];
$tipo_membresia = $_POST['tipo_membresia'];
$fecha_inicio = $_POST['fecha_inicio'];
$fecha_vencimiento = $_POST['fecha_vencimiento'];
$actividades = isset($_POST['actividades']) ? $_POST['actividades'] : [];

try {
    // Verificar si es edición o nuevo
    $stmt = $pdo->prepare("SELECT cedula FROM clientes WHERE cedula = ?");
    $stmt->execute([$cedula]);
    $cliente_existente = $stmt->fetch();
    
    if ($cliente_existente) {
        // Actualizar cliente existente
        // Actualizar en usuarios
        $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ?, fecha_nacimiento = ? WHERE cedula = ?");
        $stmt->execute([$nombre, $email, $fecha_nacimiento, $cedula]);
        
        // Actualizar en clientes
        $stmt = $pdo->prepare("UPDATE clientes SET apellido = ?, telefono = ?, direccion = ?, tipo_membresia = ?, fecha_inicio = ?, fecha_vencimiento = ? WHERE cedula = ?");
        $stmt->execute([$apellido, $telefono, $direccion, $tipo_membresia, $fecha_inicio, $fecha_vencimiento, $cedula]);
        
        $mensaje = 'Cliente actualizado correctamente';
    } else {
        // Verificar si el usuario ya existe en la tabla usuarios
        $stmt = $pdo->prepare("SELECT cedula FROM usuarios WHERE cedula = ?");
        $stmt->execute([$cedula]);
        $usuario_existente = $stmt->fetch();
        
        if ($usuario_existente) {
            // Actualizar usuario existente (mantener rol existente, solo cambiar estado a activo)
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ?, fecha_nacimiento = ?, estado = 'activo' WHERE cedula = ?");
            $stmt->execute([$nombre, $email, $fecha_nacimiento, $cedula]);
        } else {
            // Crear nuevo usuario
            $contrasena_default = password_hash('cliente123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (cedula, nombre, email, usuario, contrasena, fecha_nacimiento, rol, estado) VALUES (?, ?, ?, ?, ?, ?, 'cliente', 'activo')");
            $stmt->execute([$cedula, $nombre, $email, $cedula, $contrasena_default, $fecha_nacimiento]);
        }
        
        // Insertar en clientes
        $stmt = $pdo->prepare("INSERT INTO clientes (cedula, apellido, telefono, direccion, tipo_membresia, fecha_inicio, fecha_vencimiento) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$cedula, $apellido, $telefono, $direccion, $tipo_membresia, $fecha_inicio, $fecha_vencimiento]);
        
        $mensaje = 'Cliente creado correctamente';
    }
    
    // Manejar actividades
    // Eliminar actividades existentes
    $stmt = $pdo->prepare("DELETE FROM cliente_actividad WHERE cliente_id = ?");
    $stmt->execute([$cedula]);
    
    // Insertar nuevas actividades
    foreach ($actividades as $actividad_id) {
        $stmt = $pdo->prepare("INSERT INTO cliente_actividad (cliente_id, actividad_id) VALUES (?, ?)");
        $stmt->execute([$cedula, $actividad_id]);
    }
    
    $_SESSION['mensaje'] = $mensaje;
    $_SESSION['tipo_mensaje'] = 'success';
    
} catch (PDOException $e) {
    $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
    $_SESSION['tipo_mensaje'] = 'danger';
}

header('Location: ../clientes.php');
exit;
?>