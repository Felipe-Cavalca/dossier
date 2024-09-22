CREATE TABLE IF NOT EXISTS folder (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES "user" (id) ON DELETE CASCADE,
    parent_id INTEGER DEFAULT NULL REFERENCES folder (id) ON DELETE CASCADE,
    original_folder_id INTEGER DEFAULT NULL REFERENCES folder (id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL CHECK (
        name ~ '^[^\\/:*?"<>|]{1,255}$'
    ),
    UNIQUE (user_id, parent_id, name)
);

SELECT create_log_trigger ('folder');
