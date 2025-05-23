DROP TRIGGER IF EXISTS trg_refresh_on_folders ON folders;

DROP TRIGGER IF EXISTS trg_refresh_on_files ON files;

DROP FUNCTION IF EXISTS trg_refresh_user_file_structure ();

DROP VIEW IF EXISTS file_structure;

DROP MATERIALIZED VIEW IF EXISTS user_file_structure;

-- Create materialized view to show the folder and file structure of a user
CREATE MATERIALIZED VIEW user_file_structure AS
WITH RECURSIVE
    folder_paths AS (
        SELECT
            id,
            user_id,
            parent_id,
            name,
            name::TEXT AS path
        FROM folders
        WHERE
            parent_id IS NULL
        UNION ALL
        SELECT f.id, f.user_id, f.parent_id, f.name, (fp.path || '/' || f.name)::TEXT
        FROM folders f
            JOIN folder_paths fp ON f.parent_id = fp.id
    )
SELECT
    f.id,
    f.user_id,
    'folder' AS type,
    f.name AS name,
    '/' || fp.path AS path, -- Adiciona '/' no início
    -- parent_path sem barra final, mas com barra inicial (raiz é só '/')
    CASE
        WHEN f.parent_id IS NULL THEN '/'
        ELSE '/' || fp2.path
    END AS parent_path,
    (
        SELECT fl.changed
        FROM folders_log fl
        WHERE
            fl.original_id = f.id
            AND fl.action IN ('UPDATE', 'INSERT')
        ORDER BY fl.changed DESC
        LIMIT 1
    ) AS modified,
    (
        -- Soma arquivos
        COALESCE(
            (
                SELECT SUM(fi2.size)
                FROM files fi2
                    LEFT JOIN folder_paths fp2 ON fi2.folder_id = fp2.id
                WHERE
                    fp2.path LIKE fp.path || '%'
            ),
            0
        ) +
        -- Conta pastas (inclusive a própria)
        (
            SELECT COUNT(*)
            FROM folder_paths fp2
            WHERE
                fp2.path LIKE fp.path || '%'
        )
    ) AS size
FROM
    folders f
    JOIN folder_paths fp ON f.id = fp.id
    LEFT JOIN folder_paths fp2 ON f.parent_id = fp2.id
UNION ALL
SELECT
    fi.id,
    fi.user_id,
    'file' AS type,
    fi.name AS name,
    '/' || COALESCE(fp.path || '', '') || CASE
        WHEN fp.path IS NULL THEN ''
        ELSE '/'
    END || fi.name AS path, -- Adiciona '/' no início
    -- parent_path sem barra final, mas com barra inicial (raiz é só '/')
    CASE
        WHEN fp.id IS NULL THEN '/'
        ELSE '/' || fp.path
    END AS parent_path,
    (
        SELECT fl.changed
        FROM files_log fl
        WHERE
            fl.original_id = fi.id
            AND fl.action IN ('UPDATE', 'INSERT')
        ORDER BY fl.changed DESC
        LIMIT 1
    ) AS modified,
    fi.size
FROM files fi
    LEFT JOIN folder_paths fp ON fi.folder_id = fp.id;

-- Create normal view
CREATE OR REPLACE VIEW file_structure AS
SELECT *
FROM user_file_structure;

-- Create indexes to optimize queries
CREATE INDEX idx_objects_path ON user_file_structure (path);

CREATE INDEX idx_objects_user_id ON user_file_structure (user_id);

CREATE INDEX idx_objects_type_path ON user_file_structure (type, path);

-- Create function to refresh the view
CREATE OR REPLACE FUNCTION trg_refresh_user_file_structure()
RETURNS trigger AS $$
BEGIN
    REFRESH MATERIALIZED VIEW user_file_structure;
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

-- Create triggers to refresh the view
CREATE TRIGGER trg_refresh_on_folders
AFTER INSERT OR UPDATE OR DELETE ON folders
FOR EACH STATEMENT EXECUTE FUNCTION trg_refresh_user_file_structure();

CREATE TRIGGER trg_refresh_on_files
AFTER INSERT OR UPDATE OR DELETE ON files
FOR EACH STATEMENT EXECUTE FUNCTION trg_refresh_user_file_structure();
