<?php
session_start();
require '../database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $cedula = trim($_POST['cedula']);
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);
    $tipo_membresia = trim($_POST['tipo_membresia']);
    $estado = trim($_POST['estado']);
    $actividades = isset($_POST['actividades']) ? $_POST['actividades'] : [];

    // Validar datos
    if (empty($cedula)) {
        $_SESSION['mensaje'] = "La cédula es obligatoria";
        $_SESSION['tipo_mensaje'] = 'danger';
        header('Location: ../clientes.php?action=' . ($id > 0 ? 'edit&id=' . $id : 'add'));
        exit;
    }

    // Verificar si la cédula ya existe para otro cliente
    $stmt = $pdo->prepare("SELECT id FROM clientes WHERE cedula = ? AND id != ?");
    $stmt->execute([$cedula, $id]);
    if ($stmt->fetch()) {
        $_SESSION['mensaje'] = "La cédula ya está registrada para otro cliente";
        $_SESSION['tipo_mensaje'] = 'danger';
        header('Location: ../clientes.php?action=' . ($id > 0 ? 'edit&id=' . $id : 'add'));
        exit;
    }

    // Iniciar transacción para asegurar integridad de datos
    $pdo->beginTransaction();

    try {
        if ($id > 0) {
            // Actualizar cliente existente
            $stmt = $pdo->prepare("UPDATE clientes SET 
                cedula = ?, 
                nombre = ?, 
                apellido = ?, 
                email = ?, 
                telefono = ?, 
                direccion = ?, 
                tipo_membresia = ?, 
                estado = ? 
                WHERE id = ?");
            
            if (!$stmt->execute([$cedula, $nombre, $apellido, $email, $telefono, $direccion, $tipo_membresia, $estado, $id])) {
                throw new Exception("Error al actualizar el cliente");
            }
            
            $mensaje = "Cliente actualizado correctamente";
        } else {
            // Crear nuevo cliente
            $stmt = $pdo->prepare("INSERT INTO clientes 
                (cedula, nombre, apellido, email, telefono, direccion, tipo_membresia, estado, fecha_registro) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            
            if (!$stmt->execute([$cedula, $nombre, $apellido, $email, $telefono, $direccion, $tipo_membresia, $estado])) {
                throw new Exception("Error al crear el cliente");
            }
            
            $id = $pdo->lastInsertId();
            $mensaje = "Cliente creado correctamente";
        }
        
        // Gestionar actividades solo si tenemos un ID válido
        if ($id > 0) {
            // Eliminar actividades existentes del cliente
            $stmt = $pdo->prepare("DELETE FROM cliente_actividad WHERE cliente_id = ?");
            $stmt->execute([$id]);
            
            // Insertar nuevas actividades seleccionadas
            if (!empty($actividades)) {
                $stmt = $pdo->prepare("INSERT INTO cliente_actividad (cliente_id, actividad_id) VALUES (?, ?)");
                foreach ($actividades as $actividadId) {
                    $actividadId = (int)$actividadId;
                    if ($actividadId > 0) {
                        $stmt->execute([$id, $actividadId]);
                    }
                }
            }
        }
        
        // Confirmar todas las operaciones
        $pdo->commit();
        
        $_SESSION['mensaje'] = $mensaje;
        $_SESSION['tipo_mensaje'] = 'success';
    } catch (Exception $e) {
        // Revertir cambios en caso de error
        $pdo->rollBack();
        
        $_SESSION['mensaje'] = $e->getMessage();
        $_SESSION['tipo_mensaje'] = 'danger';
        
        header('Location: ../clientes.php?action=' . ($id > 0 ? 'edit&id=' . $id : 'add'));
        exit;
    }

    // Redirigir a la lista principal de clientes
    header('Location: ../clientes.php');
    exit;
}

// Si no es método POST, redirigir a lista principal
header('Location: ../clientes.php');
exit;