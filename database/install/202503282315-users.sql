CREATE TABLE IF NOT EXISTS users (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    role_id UUID REFERENCES roles(id) ON DELETE RESTRICT,
    name VARCHAR(50) NOT NULL
        CHECK (name ~ '^[A-Za-zÀ-ÿ ]+$'),
    userName VARCHAR(50) UNIQUE
        CHECK (userName IS NULL OR userName ~ '^[A-Za-z0-9_]{3,50}$'),
    email VARCHAR(100) UNIQUE NOT NULL
        CHECK (email ~* '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$'),
    password TEXT NOT NULL
);

SELECT create_log_trigger ('users');

-- senha padrão: 123456
INSERT INTO users (name, userName, email, password, role_id) VALUES ('admin','admin','admin@dossier.com', '$2y$10$Vc3bOeteb7tGZ9FiYt5ZAOMmIQ69Xp5hxpwQ7davtvWurEaypzXH2', (SELECT id FROM roles WHERE code = 'admin'))
ON CONFLICT (email) DO NOTHING;
