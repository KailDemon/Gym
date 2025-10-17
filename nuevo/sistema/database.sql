CREATE DATABASE IF NOT EXISTS gimnasio;
USE gimnasio;

-- Tabla de usuarios administrativos
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'staff') DEFAULT 'staff',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de clientes
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    telefono VARCHAR(20),
    direccion TEXT,
    fecha_nacimiento DATE,
    tipo_membresia ENUM('basica', 'premium', 'vip') DEFAULT 'basica',
    fecha_inicio DATE,
    fecha_vencimiento DATE,
    estado ENUM('activo', 'inactivo', 'suspendido') DEFAULT 'activo',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE clientes ADD cedula VARCHAR(20) NOT NULL DEFAULT '';

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
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
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
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (actividad_id) REFERENCES actividades(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Insertar usuario admin por defecto
INSERT INTO usuarios (nombre, email, usuario, contrasena, rol) 
VALUES ('Administrador', 'admin@example.com', 'admin', SHA2('admin123', 256), 'admin');

-- Insertar actividades base
INSERT IGNORE INTO actividades (nombre) VALUES 
    ('gimnasio'),
    ('boxeo'),
    ('taekwondo'),
    ('spinning');

-- Insertar algunos clientes de ejemplo
INSERT INTO clientes (nombre, apellido, email, telefono, tipo_membresia, fecha_inicio, fecha_vencimiento)
VALUES 
    ('Juan', 'Pérez', 'juan@example.com', '555-1234', 'premium', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR)),
    ('María', 'García', 'maria@example.com', '555-5678', 'basica', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH)),
    ('Carlos', 'López', 'carlos@example.com', '555-9012', 'vip', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 2 YEAR));

-- Asignar actividades a los clientes
INSERT INTO cliente_actividad (cliente_id, actividad_id)
VALUES 
    (1, (SELECT id FROM actividades WHERE nombre = 'gimnasio')),
    (1, (SELECT id FROM actividades WHERE nombre = 'boxeo')),
    (2, (SELECT id FROM actividades WHERE nombre = 'spinning')),
    (3, (SELECT id FROM actividades WHERE nombre = 'taekwondo'));