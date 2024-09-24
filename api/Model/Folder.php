<?php

namespace Bifrost\Model;

use Bifrost\Core\Database;

class Folder
{
    private Database $database;

    public function __construct()
    {
        $this->database = new Database();
    }

    public function getIdByPath(string|array $path, int $userId): ?int
    {
        $pathString = is_array($path) ? implode('/', $path) : $path;

        // Monta a query SQL com a CTE recursiva
        $sql = "WITH RECURSIVE folder_cte AS (
                    SELECT id, name::text AS path, parent_id, user_id
                    FROM folder
                    WHERE
                        parent_id IS NULL
                        AND user_id = :user_id
                    UNION ALL
                    SELECT f.id, TRIM(BOTH ' ' FROM folder_cte.path || '/' || f.name)::text AS path, f.parent_id, f.user_id
                    FROM folder f
                    INNER JOIN folder_cte ON f.parent_id = folder_cte.id
                    WHERE
                        f.user_id = folder_cte.user_id
            )
            SELECT id
            FROM folder_cte
            WHERE
                path = :full_path;
            ";

        $folder = $this->database->list($sql, [
            ':full_path' => $path,
            ':user_id' => $userId
        ]);

        return $folder[0]['id'] ?? null;
    }

    public function getById(int $folderId): ?array
    {
        $folder = $this->database->query(
            select: ["f.*", "fl.changed"],
            from: "folder f",
            join: [
                "JOIN folder_log fl ON fl.original_id = f.id"
            ],
            where: "f.id = :id",
            order: "fl.changed DESC",
            limit: 1,
            params: [':id' => $folderId]
        );

        return $folder[0] ?? null;
    }

    public function getContent(int|null $folderId, int $userId): array
    {
        $folders = $this->database->query(
            select: ["f.*", "fl.changed"],
            from: "folder f",
            join: [
                "JOIN folder_log fl ON fl.original_id = f.id"
            ],
            where: [
                "f.parent_id" => $folderId,
                "f.user_id" => $userId
            ],
            // order: "fl.changed DESC"
        );

        /*
        $files = $this->database->list(
            "SELECT * FROM file WHERE folder_id = :id",
            [':id' => $folderId]
        );
        */

        return [
            'folders' => $folders,
            // 'files' => $files
        ];
    }
}
