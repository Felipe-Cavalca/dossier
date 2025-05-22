-- criar view para mostrar a estrutura de pastas e arquivos de um usuário
CREATE MATERIALIZED VIEW user_file_structure AS
WITH RECURSIVE
    folder_paths (
        id,
        user_id,
        parent_id,
        name,
        path
    ) AS (
        SELECT
            id,
            user_id,
            parent_id,
            name,
            name::TEXT AS path -- converte explicitamente para TEXT
        FROM folders
        WHERE
            parent_id IS NULL
        UNION ALL
        SELECT f.id, f.user_id, f.parent_id, f.name, (fp.path || '/' || f.name)::TEXT
        FROM folders f
            JOIN folder_paths fp ON f.parent_id = fp.id
    )
SELECT f.id, f.user_id, 'pasta' AS tipo, fp.path AS caminho
FROM folders f
    JOIN folder_paths fp ON f.id = fp.id
UNION ALL
SELECT fi.id, fi.user_id, 'arquivo' AS tipo, COALESCE(fp.path || '/', '') || fi.name AS caminho
FROM files fi
    LEFT JOIN folder_paths fp ON fi.folder_id = fp.id;

-- Cria view normal
CREATE OR REPLACE VIEW file_structure AS
SELECT *
FROM user_file_structure;

-- Cria indices para otimizar consultas
CREATE INDEX idx_objetos_caminho ON user_file_structure (caminho);

CREATE INDEX idx_objetos_user_id ON user_file_structure (user_id);

CREATE INDEX idx_objetos_tipo_caminho ON user_file_structure (tipo, caminho);

-- Cria função para atualizar a view
CREATE OR REPLACE FUNCTION trg_refresh_user_file_structure()
RETURNS trigger AS $$
BEGIN
    REFRESH MATERIALIZED VIEW user_file_structure;
    RETURN NULL; -- não altera tupla
END;
$$ LANGUAGE plpgsql;

-- Cria trigger para atualizar a view
-- Trigger para a tabela folders
CREATE TRIGGER trg_refresh_on_folders
AFTER INSERT OR UPDATE OR DELETE ON folders
FOR EACH STATEMENT EXECUTE FUNCTION trg_refresh_user_file_structure();

-- Trigger para a tabela files
CREATE TRIGGER trg_refresh_on_files
AFTER INSERT OR UPDATE OR DELETE ON files
FOR EACH STATEMENT EXECUTE FUNCTION trg_refresh_user_file_structure();
