-- Base de datos para el sistema de Stock y Ventas
CREATE DATABASE IF NOT EXISTS control_stock;
USE control_stock;

-- Usuarios que pueden loguearse al sistema
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dni VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NULL,
    password VARCHAR(255) NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    rol ENUM('admin', 'vendedor', 'cliente') NOT NULL DEFAULT 'cliente'
);

-- Productos en stock
CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0
);

-- Historial de ventas
CREATE TABLE IF NOT EXISTS ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    producto_nombre VARCHAR(150) NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    usuario_id INT,
    cliente_id INT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('ACTIVA', 'MODIFICADA', 'CANCELADA') NOT NULL DEFAULT 'ACTIVA',
    fecha_modificacion DATETIME NULL,
    motivo_cancelacion VARCHAR(500) NULL,
    FOREIGN KEY (producto_id) REFERENCES productos(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Auditoría de cambios y cancelaciones de ventas
CREATE TABLE IF NOT EXISTS venta_historial (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venta_id INT NOT NULL,
    usuario_id INT NULL,
    tipo VARCHAR(40) NOT NULL,
    cantidad_anterior INT NULL,
    cantidad_nueva INT NULL,
    total_anterior DECIMAL(10,2) NULL,
    total_nuevo DECIMAL(10,2) NULL,
    estado_anterior VARCHAR(20) NOT NULL,
    estado_nuevo VARCHAR(20) NOT NULL,
    motivo VARCHAR(500) NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (venta_id) REFERENCES ventas(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);
