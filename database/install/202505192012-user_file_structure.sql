CREATE OR REPLACE VIEW file_structure AS
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
