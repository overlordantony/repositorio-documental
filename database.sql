CREATE DATABASE IF NOT EXISTS repositorio_docs
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE repositorio_docs;

-- ------------------------------------------------------------
-- 1. CATEGORÍAS
-- ------------------------------------------------------------
CREATE TABLE categorias (
  id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre   VARCHAR(100) NOT NULL UNIQUE,
  color_texto VARCHAR(7) NOT NULL DEFAULT '#185FA5',
  color_fondo VARCHAR(7) NOT NULL DEFAULT '#E6F1FB',
  activo   TINYINT(1)  NOT NULL DEFAULT 1,
  orden    SMALLINT    NOT NULL DEFAULT 0,
  creado_en TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO categorias (nombre, color_texto, color_fondo, orden) VALUES
  ('Test', '#3B6D11', '#EAF3DE', 1),
  ('Otros',     '#3C3489', '#EEEDFE', 2);

-- ------------------------------------------------------------
-- 2. DEPENDENCIAS
-- ------------------------------------------------------------
CREATE TABLE dependencias (
  id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre   VARCHAR(150) NOT NULL UNIQUE,
  sigla    VARCHAR(20),
  activo   TINYINT(1)  NOT NULL DEFAULT 1,
  creado_en TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO dependencias (nombre, sigla) VALUES
  ('Responsable 1', 'R1'),
  ('Test', 'T');

-- ------------------------------------------------------------
-- 3. USUARIOS ADMIN
-- Roles: superadmin | editor | viewer
-- ------------------------------------------------------------
CREATE TABLE usuarios (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(150) NOT NULL,
  usuario       VARCHAR(80)  NOT NULL UNIQUE,
  email         VARCHAR(200) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  rol           ENUM('superadmin','editor','viewer') NOT NULL DEFAULT 'editor',
  activo        TINYINT(1)  NOT NULL DEFAULT 1,
  ultimo_acceso TIMESTAMP   NULL,
  creado_en     TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  creado_por    INT UNSIGNED NULL,
  FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- superadmin por defecto: overlord / 12345678
INSERT INTO usuarios (nombre, usuario, email, password_hash, rol) VALUES (
  'Overlord',
  'overlord',
  'overlord@overlordantony.com',
  '$2y$12$BGGS6cjk/tErsTJ6XRzcZu9OqZicS2VsaWrBGj4i.u6RS1H6ksBCS',
  'superadmin'
);

-- ------------------------------------------------------------
-- 4. DOCUMENTOS
-- ------------------------------------------------------------
CREATE TABLE documentos (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  titulo           VARCHAR(300) NOT NULL,
  descripcion      TEXT,
  categoria_id     INT UNSIGNED NOT NULL,
  dependencia_id   INT UNSIGNED NOT NULL,
  archivo          VARCHAR(300) NOT NULL,
  tipo_archivo     VARCHAR(10)  NOT NULL DEFAULT 'pdf',
  tamanio_kb       INT UNSIGNED NOT NULL DEFAULT 0,
  anio             YEAR        NOT NULL,
  fecha_publicacion DATE        NOT NULL,
  activo           TINYINT(1)  NOT NULL DEFAULT 1,
  subido_por       INT UNSIGNED NULL,
  creado_en        TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en   TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (categoria_id)   REFERENCES categorias(id)   ON DELETE RESTRICT,
  FOREIGN KEY (dependencia_id) REFERENCES dependencias(id)  ON DELETE RESTRICT,
  FOREIGN KEY (subido_por)     REFERENCES usuarios(id)      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Índices para búsqueda rápida
CREATE INDEX idx_doc_anio      ON documentos(anio);
CREATE INDEX idx_doc_categoria ON documentos(categoria_id);
CREATE INDEX idx_doc_activo    ON documentos(activo);
CREATE FULLTEXT INDEX idx_doc_ft ON documentos(titulo, descripcion);

-- ------------------------------------------------------------
-- 5. LOG DE ACTIVIDAD
-- ------------------------------------------------------------
CREATE TABLE log_actividad (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id  INT UNSIGNED NULL,
  accion      VARCHAR(80)  NOT NULL,
  entidad     VARCHAR(50),
  entidad_id  INT UNSIGNED,
  detalle     TEXT,
  ip          VARCHAR(45),
  creado_en   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
