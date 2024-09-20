CREATE TABLE IF NOT EXISTS "user" (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL CHECK (name ~ '^[A-Za-z ]+$'),
    username VARCHAR(50) UNIQUE NOT NULL CHECK (LENGTH(username) >= 3 AND LENGTH(username) <= 50),
    email VARCHAR(100) UNIQUE NOT NULL CHECK (email ~* '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$'),
    password TEXT NOT NULL
);

CREATE INDEX idx_user_email ON "user" (email);

SELECT create_log_trigger ('user');

-- senha padrão: 123456
INSERT INTO "user" (name, username, email, password) VALUES ('admin','admin','admin@dossier.com', '$2y$10$Vc3bOeteb7tGZ9FiYt5ZAOMmIQ69Xp5hxpwQ7davtvWurEaypzXH2');
