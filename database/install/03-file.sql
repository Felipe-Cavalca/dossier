CREATE TABLE IF NOT EXISTS file (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    folder_id INTEGER REFERENCES folder (id),
    user_id INTEGER REFERENCES "user" (id) NOT NULL,
    content TEXT NOT NULL
);

SELECT create_log_trigger('file');
