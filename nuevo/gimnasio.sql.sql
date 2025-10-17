-- Crear base de datos
CREATE DATABASE IF NOT EXISTS gimnasio;
USE gimnasio;

-- Tabla de membresías
CREATE TABLE membresias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    duracion_meses INT,
    precio DECIMAL(10,2) NOT NULL,
    beneficios TEXT,
    estado ENUM('activa', 'inactiva') DEFAULT 'activa'
);

-- Tabla de clientes
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    telefono VARCHAR(20) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    genero ENUM('Masculino', 'Femenino', 'Otro') NOT NULL,
    direccion TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    membresia_id INT,
    activo BOOLEAN DEFAULT 1,
    FOREIGN KEY (membresia_id) REFERENCES membresias(id)
);

-- Tabla de usuarios (administradores)
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    rol ENUM('admin', 'recepcion') DEFAULT 'recepcion'
);

-- Insertar membresías de ejemplo
INSERT INTO membresias (nombre, descripcion, duracion_meses, precio, beneficios) 
VALUES 
    ('Básica', 'Acceso a instalaciones básicas', 1, 30.00, 'Gimnasio, vestuarios'),
    ('Premium', 'Acceso completo', 3, 80.00, 'Gimnasio, piscina, clases grupales'),
    ('VIP', 'Acceso completo y servicios premium', 12, 300.00, 'Todo incluido, masajes, spa, nutricionista');

-- Insertar usuario administrador (contraseña: admin123)
INSERT INTO usuarios (username, password, email, rol) 
VALUES ('admin', '$2y$10$4V1a6W.PsQ9D7Z2O1x3zEeDnB0uYgJkZ7rNq1lL3vA8sC5mI6oH2', 'admin@gimnasio.com', 'admin');