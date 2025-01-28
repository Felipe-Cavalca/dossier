CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

CREATE TABLE IF NOT EXISTS roles (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    code VARCHAR(50) UNIQUE NOT NULL CHECK (code ~ '^[A-Za-z]+$'),
    name VARCHAR(50) UNIQUE NOT NULL CHECK (name ~ '^[A-Za-z ]+$'),
    description TEXT
);

CREATE INDEX idx_roles_code ON roles (code);

SELECT create_log_trigger ('roles');

INSERT INTO roles (code, name, description) VALUES ('admin', 'admin', 'Administrador')
ON CONFLICT (code) DO NOTHING;

INSERT INTO roles (code, name, description) VALUES ('manager', 'manager', 'Gestor')
ON CONFLICT (code) DO NOTHING;

INSERT INTO roles (code, name, description) VALUES ('user', 'user', 'Usuário')
ON CONFLICT (code) DO NOTHING;

INSERT INTO roles (code, name, description) VALUES ('visitor', 'visitor', 'Visitante')
ON CONFLICT (code) DO NOTHING;
