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
use Bifrost\DataTypes\DateTime;

class Folder
{
    private static string $table = "folders";

    /**
     * Retorna os dados de uma pasta a partir do ID
     * @param UUID $id
     * @return ClassFolder|null
     */
    public static function getById(UUID $id): ?ClassFolder
    {
        return new ClassFolder(
            id: $id,
            allData: self::list(id: $id, first: true)
        );
    }

    /**
     * Verifica se uma pasta existe no banco de dados
     * @param UUID|null $id ID da pasta
     * @param FolderName|null $name Nome da pasta
     * @param ClassFolder|null $parent Pasta Pai
     * @param User|null $user Usuário dono da pasta
     * @return bool
     */
    public static function exists(
        ?UUID $id = null,
        ?FolderName $name = null,
        ?ClassFolder $parent = null,
        ?User $user = null,
    ): bool {
        $settings = new Settings();
        $cache = new Cache();

        $conditions = self::buildFolderQuery(
            id: $id,
            user: $user,
            parent: $parent,
            name: $name
        );

        $cacheKey = Cache::buildCacheKey(entity: self::$table, conditions: $conditions) . ':exists';

        $result = $cache->get($cacheKey, function () use ($conditions) {
            $database = new Database();
            return $database->exists(table: self::$table, where: $conditions);
        }, $settings->CACHE_QUERY_TIME);

        return (bool) $result;
    }

    public static function list(
        ?UUID $id = null,
        ?FolderName $name = null,
        ?ClassFolder $parent = null,
        ?User $user = null,
        ?bool $first = false
    ): array {
        $settings = new Settings();
        $cache = new Cache();

        $conditions = self::buildFolderQuery(
            id: $id,
            user: $user,
            parent: $parent,
            name: $name,
            alias: "f."
        );

        $cacheKey = Cache::buildCacheKey(entity: self::$table, conditions: $conditions);

        $folders = $cache->get($cacheKey, function () use ($conditions) {
            $database = new Database();
            return $database->query(
                select: [
                    "f.id as id",
                    "f.user_id as user_id",
                    "f.parent_id as parent_id",
                    "f.name as name",
                    "fl.changed"
                ],
                from: "folders f",
                join: [
                    "JOIN folders_log fl ON fl.original_id = f.id"
                ],
                where: $conditions,
                order: "fl.changed DESC"
            );
        }, $settings->CACHE_QUERY_TIME);

        if ($first && !empty($folders)) {
            return self::mapArrayToFolder($folders[0]);
        }

        foreach ($folders as &$folder) {
            $folder = self::mapArrayToFolder($folder);
        }

        return !empty($folders) ? $folders : null;
    }

    /**
     * Cria uma nova pasta no banco de dados
     * @param User $user Usuário dono da pasta
     * @param FolderName $name Nome da pasta
     * @param ?ClassFolder $parent Pasta Pai
     * @return ClassFolder Nova pasta criada
     * @see self::getById() Retorno é o mesmo desta função
     */
    public static function new(User $user, FolderName $name, ?ClassFolder $parent = null): ClassFolder
    {
        $folder = self::buildFolderQuery(
            user: $user,
            parent: $parent,
            name: $name
        );

        $database = new Database();

        $folderId = $database->insert(
            table: "folders",
            data: $folder,
            returning: "id"
        );

        return self::getById(new UUID($folderId));
    }

    /**
     * Converte um array de dados em uma pasta
     * @param array $data Dados da pasta
     * @return array dados da pasta mapeados
     */
    private static function mapArrayToFolder(array $data): array
    {
        $result = [];

        if (isset($data["id"])) {
            $result["id"] = new UUID($data["id"]);
        }
        if (isset($data["user_id"])) {
            $result["user_id"] = new UUID($data["user_id"]);
        }
        if (isset($data["parent_id"]) && $data["parent_id"]) {
            $result["parent_id"] = new UUID($data["parent_id"]);
        }
        if (isset($data["name"])) {
            $result["name"] = new FolderName($data["name"]);
        }
        if (isset($data["changed"])) {
            $result["changed"] = new DateTime($data["changed"]);
        }

        return $result;
    }

    /**
     * Converte os parâmetros em um array de condições para a consulta
     * @param UUID|null $id ID da pasta
     * @param User|null $user Usuário dono da pasta
     * @param ClassFolder|null $parent Pasta Pai
     * @param FolderName|null $name Nome da pasta
     * @return array Condições para a consulta
     */
    private static function buildFolderQuery(
        ?UUID $id = null,
        ?User $user = null,
        ?ClassFolder $parent = null,
        ?FolderName $name = null,
        string $alias = ""
    ): array {
        $conditions = [];
        if ($id !== null) {
            $conditions[$alias . "id"] = (string) $id;
        }
        if ($user !== null) {
            $conditions[$alias . "user_id"] = (string) $user->id;
        }
        if ($parent !== null) {
            $conditions[$alias . "parent_id"] = (string) $parent->id;
        }
        if ($name !== null) {
            $conditions[$alias . "name"] = (string) $name;
        }

        return $conditions;
    }
}
