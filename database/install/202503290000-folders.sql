CREATE TABLE IF NOT EXISTS folders (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4 (),
    user_id UUID NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    parent_id UUID DEFAULT NULL REFERENCES folders (id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL CHECK (
        name ~ '^[^\\/:*?"<>|]{1,255}$'
    ),
    UNIQUE (parent_id, name)
);

-- Para casos onde o parent_id é null
CREATE UNIQUE INDEX unique_folder_with_null_parent ON folders (user_id, name)
WHERE
    parent_id IS NULL;

CREATE TABLE IF NOT EXISTS folders_log (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4 (),
    original_id UUID,
    action TEXT, -- Tipo de ação (INSERT, UPDATE, DELETE)
    old_data JSONB,
    new_data JSONB,
    changed TIMESTAMPTZ DEFAULT current_timestamp,
    system_identifier TEXT -- Identificador do sistema, pode ser NULL
);

SELECT create_log_trigger ('folders');
