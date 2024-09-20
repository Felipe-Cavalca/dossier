CREATE TABLE IF NOT EXISTS file (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL CHECK (name ~ '^[^\\/:*?"<>|]{1,255}$'),
    folder_id INTEGER DEFAULT NULL REFERENCES folder (id) ON DELETE CASCADE,
    parent_file_id INTEGER DEFAULT NULL REFERENCES file (id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES "user" (id) ON DELETE CASCADE,
    content TEXT DEFAULT NULL
);

SELECT create_log_trigger ('file');
