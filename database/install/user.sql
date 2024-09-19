CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL CHECK (username ~ '^[a-z0-9_]{3,50}$'),
    email VARCHAR(100) UNIQUE NOT NULL,
    password TEXT NOT NULL,
    home_directory VARCHAR(255) NOT NULL,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- Exemplo para criar um trigger para a tabela 'users'
SELECT create_log_trigger('users');

INSERT INTO users (name, username, email, password, home_directory) VALUES ('admin', 'admin', 'admin@dossier.com', '123456', '/var/lib/dav/data/admin');
