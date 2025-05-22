<?php

namespace Bifrost\Model;

use Bifrost\Class\Folder as ClassFolder;
use Bifrost\Class\User;
use Bifrost\Core\Cache;
use Bifrost\Core\Database;
use Bifrost\Core\Settings;
use Bifrost\DataTypes\FilePath;
use Bifrost\DataTypes\FolderName;
use Bifrost\DataTypes\UUID;
use DateTime;

class Folder
{

    /**
     * @deprecated
     */
    public static function getByPath(FilePath $path, User $user): ?UUID
    {
        // Monta a query SQL com a CTE recursiva
        $sql = "WITH RECURSIVE folder_cte AS (
                    SELECT id, name::text AS path, parent_id, user_id
                    FROM folders
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

        $database = new Database();

        $folder = $database->executeQuery($sql, [
            ':full_path' => (string) $path,
            ':user_id' => (string) $user->id
        ]);

        return $folder[0] ?? null;
    }

    /**
     * Retorna os dados de uma pasta a partir do ID
     * @param UUID $id
     * @return array{
     *     id: UUID,
     *     user_id: UUID,
     *     parent_id: ?UUID,
     *     name: FolderName,
     *     changed: DateTime
     * }
     */
    public static function getById(UUID $id): ?array
    {
        $database = new Database();
        $settings = new Settings();
        $cache = new Cache();

        $folder = $cache->get("get-folder-" . (string) $id, function () use ($database, $id) {
            return $database->query(
                select: ["f.*", "fl.changed"],
                from: "folders f",
                join: [
                    "JOIN folders_log fl ON fl.original_id = f.id"
                ],
                where: [
                    "f.id" => (string) $id
                ],
                order: "fl.changed DESC",
                limit: 1
            );
        }, $settings->CACHE_QUERY_TIME);

        $folder = $folder[0] ?? null;

        if (!empty($folder)) {
            $folder["id"] = new UUID($folder["id"]);
            $folder["user_id"] = new UUID($folder["user_id"]);
            $folder["parent_id"] = $folder["parent_id"] ? new UUID($folder["parent_id"]) : null;
            $folder["name"] = new FolderName($folder["name"]);
            $folder["changed"] = new DateTime($folder["changed"]);
        }

        return $folder;
    }

    /**
     * Valida se uma pasta já existe
     * @param FolderName $name nome da pasta
     * @param ClassFolder|User $reference Pasta pai ou o dono da pasta caso ela esteja na raiz
     * @return bool a pasta existe ou não
     */
    public static function exists(FolderName $name, ClassFolder|User $reference): bool
    {
        if ($reference instanceof ClassFolder) {
            return self::existsInParent(name: $name, parent: $reference);
        }
        if ($reference instanceof User) {
            return self::existsInUser(name: $name, user: $reference);
        }
        throw new \InvalidArgumentException("Invalid reference type. Expected ClassFolder or User.");
        return false;
    }

    /**
     * valida se uma pasta existe dentro de uma outra pasta
     * @param FolderName $name Nome da pasta
     * @param ClassFolder $parent pasta pai
     * @return bool
     */
    private static function existsInParent(FolderName $name, ClassFolder $parent): bool
    {
        $database = new Database();
        $cache = new Cache();
        $cacheKey = "folder-exists-" . md5((string) $name . ($parent ? (string) $parent->id : ""));

        $folder = $cache->get($cacheKey, function () use ($database, $name, $parent) {
            return $database->query(
                select: ["id"],
                from: "folders",
                where: [
                    "parent_id" => $parent ? (string) $parent->id : null,
                    "name" => (string) $name
                ]
            );
        }, 30);

        return !empty($folder);
    }

    /**
     * Valida se uma pasta existe na home de um usuário
     * @param FolderName $name Nome da pasta
     * @param User $user Usuário dono da pasta
     * @return bool
     */
    private static function existsInUser(FolderName $name, User $user): bool
    {
        $database = new Database();
        $cache = new Cache();
        $cacheKey = "folder-exists-" . md5((string) $name . ($user ? (string) $user->id : ""));

        $folder = $cache->get($cacheKey, function () use ($database, $name, $user) {
            return $database->query(
                select: ["id"],
                from: "folders",
                where: [
                    "user_id" => $user ? (string) $user->id : null,
                    "parent_id" => null,
                    "name" => (string) $name
                ]
            );
        }, 30);

        return !empty($folder);
    }

    /**
     * Valida se um UUID é um id de pasta
     * @param UUID $id id a ser verificado
     * @return bool valida se o ID é valido como id de pasta
     */
    public static function validId(UUID $id): bool
    {
        $database = new Database();
        $id = (string) $id;
        $result = $database->query(query: "SELECT EXISTS ( SELECT 1 FROM folders WHERE id = '" . $id . "' ) AS exists", returnFirst: true);
        return !empty($result);
    }

    /**
     * Cria uma nova pasta no banco de dados
     * @param User $user Usuário dono da pasta
     * @param FolderName $name Nome da pasta
     * @param ?ClassFolder $parent Pasta Pai
     * @return array Dados da pasta no banco (ver getById)
     * @see self::getById() Retorno é o mesmo desta função
     */
    public static function new(User $user, FolderName $name, ?ClassFolder $parent = null): array
    {
        $folder = [
            "name" => (string) $name,
            "user_id" => (string) $user->id,
            "parent_id" => $parent ? (string) $parent->id : null
        ];

        $database = new Database();
        $cache = new Cache();
        $cacheKey = "folder-exists-" . md5((string) $name . (string) ($parent ? $parent->id : $user->id));
        $cache->del($cacheKey);

        $folderId = $database->insert(
            table: "folders",
            data: $folder,
            returning: "id"
        );

        return self::getById(new UUID($folderId));
    }

    /**
     * Retorna a listagem de pastas
     * @param User $user Usuário dono da pasta
     * @return array
     */
    public static function list(?User $user = null): array
    {
        if ($user) {
            return self::getByUser($user);
        }
        return self::listAll();
    }

    /**
     * Retorna todas as pastas do sistema
     * @return array
     */
    private static function listAll(): array
    {
        $database = new Database();
        $settings = new Settings();
        $cache = new Cache();

        $folders = $cache->get("list-all-folders", function () use ($database) {
            return $database->query(
                select: ["f.*", "fl.changed"],
                from: "folders f",
                join: [
                    "JOIN folders_log fl ON fl.original_id = f.id"
                ],
                order: "fl.changed DESC"
            );
        }, $settings->CACHE_QUERY_TIME);

        return $folders;
    }

    /**
     * Retorna todas as pastas de um usuário
     * @param User $user Usuário dono da pasta
     * @return array
     */
    private static function getByUser(User $user): array
    {
        $database = new Database();
        $settings = new Settings();
        $cache = new Cache();

        $folders = $cache->get("list-user-folders-" . (string) $user->id, function () use ($database, $user) {
            return $database->query(
                select: ["f.*", "fl.changed"],
                from: "folders f",
                join: [
                    "JOIN folders_log fl ON fl.original_id = f.id"
                ],
                where: [
                    "f.user_id" => (string) $user->id
                ],
                order: "fl.changed DESC"
            );
        }, $settings->CACHE_QUERY_TIME);

        return $folders;
    }
}
