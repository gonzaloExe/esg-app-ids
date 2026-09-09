CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pc_identificador VARCHAR(100) UNIQUE NOT NULL,
    nombre_usuario VARCHAR(100) NOT NULL,
    rol ENUM('superadmin','encargado_departamento','usuario') DEFAULT 'usuario',
    departamento VARCHAR(100) DEFAULT 'General',
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_ultima_conexion DATETIME,
    estado_pc ENUM('buena','lenta','fallando') DEFAULT 'buena',
    estado_pc_fecha_asignacion DATETIME NULL,
    estado_pc_asignado_por VARCHAR(100) NULL,
    estado_pc_comentario TEXT NULL,
    sistema_operativo VARCHAR(100),
    arquitectura VARCHAR(50),
    procesador VARCHAR(200),
    cpu_nombre VARCHAR(200),
    cpu_cores INT,
    cpu_logical INT,
    ram_total_gb DECIMAL(10,2),
    ram_disponible_gb DECIMAL(10,2),
    placa_manufacturer VARCHAR(100),
    placa_product VARCHAR(100),
    disco_modelo VARCHAR(200),
    disco_tamano_gb DECIMAL(10,2),
    disco_c_total_gb DECIMAL(10,2),
    disco_c_libre_gb DECIMAL(10,2),
    disco_c_porcentaje_libre DECIMAL(5,2),
    mac_address VARCHAR(50),
    ip_local VARCHAR(50),
    version_php VARCHAR(20),
    ultima_deteccion_hardware DATETIME
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT NOT NULL,
    foto VARCHAR(255) NULL,
    pc_origen VARCHAR(100) NOT NULL,
    usuario_origen VARCHAR(100) NOT NULL,
    departamento VARCHAR(100) NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('pendiente','aprobado','rechazado','resuelto') DEFAULT 'pendiente',
    aprobado_por VARCHAR(100) NULL,
    fecha_aprobacion DATETIME NULL,
    rechazado_por VARCHAR(100) NULL,
    fecha_rechazo DATETIME NULL,
    motivo_rechazo TEXT NULL,
    resuelto_por VARCHAR(100) NULL,
    fecha_resolucion DATETIME NULL,
    comentarios_resolucion TEXT NULL,
    estado_pc_origen ENUM('buena','lenta','fallando') NULL,
    hardware_snapshot JSON NULL,
    INDEX idx_ticket_pc (pc_origen),
    INDEX idx_ticket_dept (departamento),
    INDEX idx_ticket_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS departamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) UNIQUE NOT NULL,
    descripcion TEXT,
    encargado_pc VARCHAR(100) NULL,
    INDEX idx_dep_encargado (encargado_pc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS configuracion (
    clave VARCHAR(100) PRIMARY KEY,
    valor TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO configuracion(clave,valor) VALUES
('departamento_por_defecto','General'),
('version_sistema','2.0'),
('mostrar_hardware_admin','true'),
('tickets_por_pagina','10'),
('instalado','false')
ON DUPLICATE KEY UPDATE valor=VALUES(valor);

INSERT INTO departamentos(nombre,descripcion)
VALUES('General','Departamento general')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);
