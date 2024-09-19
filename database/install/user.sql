CREATE TABLE IF NOT EXISTS user (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL CHECK (username ~ '^[a-z0-9_]{3,50}$'),
    email VARCHAR(100) UNIQUE NOT NULL,
    password TEXT NOT NULL
);

SELECT create_log_trigger('user');

-- senha padrão: 123456
INSERT INTO user (name, username, email, password) VALUES ('admin', 'admin', 'admin@dossier.com', '$2y$10$Vc3bOeteb7tGZ9FiYt5ZAOMmIQ69Xp5hxpwQ7davtvWurEaypzXH2');
