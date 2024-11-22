CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

CREATE TABLE IF NOT EXISTS folder (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    parent_id UUID DEFAULT NULL REFERENCES folder (id) ON DELETE CASCADE,
    original_folder_id UUID DEFAULT NULL REFERENCES folder (id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL CHECK (
        name ~ '^[^\\/:*?"<>|]{1,255}$'
    ),
    UNIQUE (user_id, parent_id, name)
);

-- Para casos onde o parent_id é null
CREATE UNIQUE INDEX unique_folder_with_null_parent
ON folder (user_id, name)
WHERE parent_id IS NULL;

SELECT create_log_trigger ('folder');
