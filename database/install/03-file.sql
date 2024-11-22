CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

CREATE TABLE IF NOT EXISTS file (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    name VARCHAR(255) NOT NULL CHECK (name ~ '^[^\\/:*?"<>|]{1,255}$'),
    folder_id UUID DEFAULT NULL REFERENCES folder (id) ON DELETE CASCADE,
    parent_file_id UUID DEFAULT NULL REFERENCES file (id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    content TEXT DEFAULT NULL,
    UNIQUE (user_id, folder_id, name)
);

SELECT create_log_trigger ('file');
