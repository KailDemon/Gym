CREATE DATABASE IF NOT EXISTS gimnasio;
USE gimnasio;

-- Tabla principal de usuarios
CREATE TABLE usuarios (
    cedula INT(20) PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    fecha_nacimiento DATE,
    rol ENUM('admin', 'staff', 'cliente') DEFAULT 'cliente',
    estado ENUM('activo', 'pendiente', 'inactivo') DEFAULT 'pendiente',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de clientes (hereda de usuarios)
CREATE TABLE clientes (
    cedula INT(20) PRIMARY KEY,
    apellido VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    direccion TEXT,
    tipo_membresia ENUM('basica', 'premium', 'vip') DEFAULT 'basica',
    fecha_inicio DATE,
    fecha_vencimiento DATE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cedula) REFERENCES usuarios(cedula) ON DELETE CASCADE
);

-- Tabla de staff (hereda de usuarios)
CREATE TABLE staff (
    cedula INT(20) PRIMARY KEY,
    apellido VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    direccion TEXT,
    fecha_inicio DATE,
    fecha_vencimiento DATE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cedula) REFERENCES usuarios(cedula) ON DELETE CASCADE
);

-- Tabla de actividades
CREATE TABLE IF NOT EXISTS actividades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
);

-- Tabla de relación cliente-actividad
CREATE TABLE IF NOT EXISTS cliente_actividad (
    cliente_id INT NOT NULL,
    actividad_id INT NOT NULL,
    PRIMARY KEY (cliente_id, actividad_id),
    FOREIGN KEY (cliente_id) REFERENCES clientes(cedula) ON DELETE CASCADE,
    FOREIGN KEY (actividad_id) REFERENCES actividades(id) ON DELETE CASCADE
);

-- Tabla de asistencias
CREATE TABLE asistencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    actividad_id INT NOT NULL,
    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    usuario_id INT NOT NULL,
    reportada TINYINT(1) DEFAULT 0 NOT NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(cedula) ON DELETE CASCADE,
    FOREIGN KEY (actividad_id) REFERENCES actividades(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(cedula) ON DELETE CASCADE
);

-- Insertar usuario admin por defecto
INSERT INTO usuarios (cedula, nombre, email, usuario, contrasena, rol, estado) 
VALUES (123456789, 'Administrador', 'admin@example.com', 'admin', SHA2('admin123', 256), 'admin', 'activo');

-- Insertar actividades base
INSERT IGNORE INTO actividades (nombre) VALUES 
    ('gimnasio'),
    ('boxeo'),
    ('taekwondo'),
    ('spinning');

-- Insertar algunos usuarios de ejemplo (clientes)
INSERT INTO usuarios (cedula, nombre, email, usuario, contrasena, rol, estado, fecha_nacimiento) VALUES 
    (25355201, 'Juan', 'juan@example.com', 'juan123', SHA2('cliente123', 256), 'cliente', 'activo', '1990-01-01'),
    (25356201, 'María', 'maria@example.com', 'maria456', SHA2('cliente123', 256), 'cliente', 'activo', '1992-05-15'),
    (25355275, 'Carlos', 'carlos@example.com', 'carlos789', SHA2('cliente123', 256), 'cliente', 'activo', '1985-08-20'),
    (25357755, 'Jose', 'jose@example.com', 'jose012', SHA2('cliente123', 256), 'cliente', 'activo', '1988-11-30');

-- Insertar datos en tabla clientes
INSERT INTO clientes (cedula, apellido, telefono, direccion, tipo_membresia, fecha_inicio, fecha_vencimiento) VALUES 
    (25355201, 'Pérez', '555-1234', 'Calle 123, Ciudad', 'premium', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR)),
    (25356201, 'García', '555-5678', 'Avenida 456, Ciudad', 'basica', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH)),
    (25355275, 'López', '555-9012', 'Boulevard 789, Ciudad', 'vip', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 2 YEAR)),
    (25357755, 'López', '555-9012', 'Boulevard 789, Ciudad', 'vip', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 2 YEAR));

-- Asignar actividades a los clientes
INSERT INTO cliente_actividad (cliente_id, actividad_id) VALUES 
    (25355201, (SELECT id FROM actividades WHERE nombre = 'gimnasio')),
    (25356201, (SELECT id FROM actividades WHERE nombre = 'boxeo')),
    (25355275, (SELECT id FROM actividades WHERE nombre = 'spinning')),
    (25357755, (SELECT id FROM actividades WHERE nombre = 'taekwondo'));

-- Procedimiento almacenado para crear usuarios completos
DELIMITER //
CREATE PROCEDURE CrearUsuarioCompleto(
    IN p_cedula INT,
    IN p_nombre VARCHAR(100),
    IN p_email VARCHAR(100),
    IN p_usuario VARCHAR(50),
    IN p_contrasena VARCHAR(255),
    IN p_fecha_nacimiento DATE,
    IN p_rol ENUM('admin', 'staff', 'cliente'),
    IN p_apellido VARCHAR(100),
    IN p_telefono VARCHAR(20),
    IN p_direccion TEXT,
    IN p_tipo_membresia ENUM('basica', 'premium', 'vip'),
    IN p_fecha_inicio DATE,
    IN p_fecha_vencimiento DATE
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Insertar en tabla usuarios
    INSERT INTO usuarios (cedula, nombre, email, usuario, contrasena, fecha_nacimiento, rol, estado)
    VALUES (p_cedula, p_nombre, p_email, p_usuario, p_contrasena, p_fecha_nacimiento, p_rol, 'activo');
    
    -- Insertar en tabla específica según el rol
    IF p_rol = 'cliente' THEN
        INSERT INTO clientes (cedula, apellido, telefono, direccion, tipo_membresia, fecha_inicio, fecha_vencimiento)
        VALUES (p_cedula, p_apellido, p_telefono, p_direccion, p_tipo_membresia, p_fecha_inicio, p_fecha_vencimiento);
    ELSEIF p_rol = 'staff' THEN
        INSERT INTO staff (cedula, apellido, telefono, direccion, fecha_inicio, fecha_vencimiento)
        VALUES (p_cedula, p_apellido, p_telefono, p_direccion, p_fecha_inicio, p_fecha_vencimiento);
    END IF;
    
    COMMIT;
END //
DELIMITER ;

-- Vista para obtener información completa de clientes
CREATE VIEW vista_clientes_completa AS
SELECT 
    u.cedula,
    u.nombre,
    c.apellido,
    CONCAT(u.nombre, ' ', c.apellido) as nombre_completo,
    u.email,
    u.usuario,
    u.fecha_nacimiento,
    u.rol,
    u.estado,
    u.fecha_creacion,
    c.telefono,
    c.direccion,
    c.tipo_membresia,
    c.fecha_inicio,
    c.fecha_vencimiento,
    c.fecha_registro
FROM usuarios u
INNER JOIN clientes c ON u.cedula = c.cedula;

-- Vista para obtener información completa de staff
CREATE VIEW vista_staff_completa AS
SELECT 
    u.cedula,
    u.nombre,
    s.apellido,
    CONCAT(u.nombre, ' ', s.apellido) as nombre_completo,
    u.email,
    u.usuario,
    u.fecha_nacimiento,
    u.rol,
    u.estado,
    u.fecha_creacion,
    s.telefono,
    s.direccion,
    s.fecha_inicio,
    s.fecha_vencimiento,
    s.fecha_registro
FROM usuarios u
INNER JOIN staff s ON u.cedula = s.cedula;