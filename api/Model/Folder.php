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

    /**
     * Retorna o ID de uma pasta a partir de seu caminho
     *
     * @param string|array $path Caminho da pasta
     * @param int $user_id ID do usuário
     * @return int|null ID da pasta
     */
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

    /**
     * Retorna as informações de uma pasta a partir de seu ID
     *
     * @param int $folderId ID da pasta
     * @return array|null Informações da pasta
     */
    public function getById(int $folderId): ?array
    {
        $folder = $this->database->query(
            select: "*",
            from: "folder",
            where: "id = :id",
            params: [':id' => $folderId]
        );

        return $folder[0] ?? null;
    }
}
