CREATE TABLE IF NOT EXISTS folder (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    parent_id INTEGER REFERENCES folder (id),
    user_id INTEGER REFERENCES "user" (id) NOT NULL
);

SELECT create_log_trigger('folder');
