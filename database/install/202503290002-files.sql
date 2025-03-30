CREATE TABLE IF NOT EXISTS files (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    folder_id UUID REFERENCES folders (id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL CHECK (
        name ~ '^[^\\/:*?"<>|]{1,255}$'
    ),
    extension VARCHAR(10) NOT NULL CHECK (
        extension ~ '^[A-Za-z0-9]{1,10}$'
    ),
    size BIGINT NOT NULL CHECK (size > 0),
    hash VARCHAR(64) NOT NULL CHECK (
        hash ~ '^[A-Fa-f0-9]{64}$'
    ),
    trash BOOLEAN DEFAULT FALSE,
    path_in_storage VARCHAR(255) NOT NULL CHECK (
        path_in_storage ~ '^[^\\:*?"<>|]{1,255}$'
    ),
    UNIQUE (user_id, folder_id, name)
);

SELECT create_log_trigger ('files');

CREATE TABLE IF NOT EXISTS files_log (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    original_id UUID,
    action TEXT, -- Tipo de ação (INSERT, UPDATE, DELETE)
    old_data JSONB,
    new_data JSONB,
    changed TIMESTAMPTZ DEFAULT current_timestamp,
    system_identifier TEXT, -- Identificador do sistema, pode ser NULL
    deleted_storage BOOLEAN DEFAULT FALSE -- Indica se o registro foi excluído do storage
);

CREATE OR REPLACE VIEW files_to_deleted AS
SELECT
    id,
    old_data->>'id' AS id_file,
    old_data->>'path_in_storage' AS path_in_storage
FROM
    files_log
WHERE
    action = 'DELETE'
    AND deleted_storage = FALSE;
