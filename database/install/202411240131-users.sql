CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

CREATE TABLE IF NOT EXISTS users (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    role_id UUID REFERENCES roles (id) ON DELETE SET NULL,
    name VARCHAR(50) NOT NULL CHECK (name ~ '^[A-Za-z ]+$'),
    userName VARCHAR(50) UNIQUE NOT NULL CHECK (LENGTH(userName) >= 3 AND LENGTH(userName) <= 50),
    email VARCHAR(100) UNIQUE NOT NULL CHECK (email ~* '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$'),
    password TEXT NOT NULL
);

CREATE INDEX idx_users_email ON users (email);
CREATE INDEX idx_users_userName ON users (userName);

SELECT create_log_trigger ('users');

-- senha padrão: 123456
INSERT INTO users (name, userName, email, password, role_id) VALUES ('admin','admin','admin@dossier.com', '$2y$10$Vc3bOeteb7tGZ9FiYt5ZAOMmIQ69Xp5hxpwQ7davtvWurEaypzXH2', (SELECT id FROM roles WHERE code = 'admin'))
ON CONFLICT (email) DO NOTHING;
