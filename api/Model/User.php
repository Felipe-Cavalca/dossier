<?php

namespace Bifrost\Model;

use Bifrost\Core\Database;
use Bifrost\DataTypes\Email;
use Bifrost\DataTypes\UUID;
use Bifrost\Class\Role as Role;
use Bifrost\Core\Cache;
use Bifrost\Core\Settings;
use Bifrost\DataTypes\Password;

class User
{
    private static string $table = "users";

    public static function getById(UUID $id): array
    {
        return self::search(["u.id" => (string) $id]) ?? [];
    }

    public static function getByEmail(Email $email): array
    {
        return self::search(["u.email" => (string) $email]) ?? [];
    }

    public static function getAll(): array
    {
        $database = new Database();
        return $database->select(
            table: self::$table . " u",
            fields: [
                "u.id",
                "r.name AS role",
                "u.name",
                "u.userName",
                "u.email",
                "ulc.changed AS created",
                "COALESCE(ulu.changed, ulc.changed) AS updated",
            ],
            join: [
                "JOIN users_log ulc ON ulc.original_id = u.id AND ulc.action = 'INSERT'",
                "LEFT JOIN users_log ulu ON ulu.original_id = u.id AND ulu.action = 'UPDATE'",
                "JOIN roles r ON r.id = u.role_id",
            ]
        );
    }

    public static function search(array $conditions): ?array
    {
        $database = new Database();
        $settings = new Settings();
        $cache = new Cache();

        $cacheKey = Cache::buildCacheKey(entity: self::$table, conditions: $conditions);

        $user = $cache->get($cacheKey, function () use ($database, $conditions) {
            return $database->select(
                table: self::$table . " u",
                fields: [
                    "u.*"
                ],
                where: $conditions,
                limit: 1
            );
        }, $settings->CACHE_QUERY_TIME);

        $user = $user[0] ?? null;

        if (!empty($user)) {
            $user["id"] = new UUID($user["id"]);
            $user["role_id"] = new UUID($user["role_id"]);
            $user["name"] = (string) $user["name"];
            $user["userName"] = (string) ($user["userName"] ?? $user["username"]);
            $user["email"] = new Email($user["email"]);
            $user["password"] = (string) $user["password"];
        }

        return $user;
    }

    public static function new(
        string $name,
        Email $email,
        Password $password,
        Role $role,
        ?string $userName = null,
    ): array {
        $database = new Database();

        $userData = [
            "name" => $name,
            "email" => (string) $email,
            "password" => (string) $password,
            "userName" => $userName,
            "role_id" => (string) $role->id
        ];

        $id = $database->insert(
            table: self::$table,
            data: $userData,
            returning: "*"
        );

        $userData["id"] = $id;

        return $userData;
    }

    public static function exists(array $conditions): bool
    {
        $settings = new Settings();
        $cache = new Cache();

        $cacheKey = Cache::buildCacheKey(entity: self::$table, conditions: $conditions) . ':exists';

        $result = $cache->get($cacheKey, function () use ($conditions) {
            $database = new Database();
            return $database->exists(table: self::$table, where: $conditions);
        }, $settings->CACHE_QUERY_TIME);

        return (bool) $result;
    }

    /**
     * Atualiza os dados de um usuário
     * @param UUID $id id do usuário
     * @param array $data dados a serem atualizados
     * @return bool true se o usuário foi atualizado
     */
    public static function update(UUID $id, array $data): bool
    {
        $database = new Database();
        return $database->update(
            table: self::$table,
            data: $data,
            where: ["id" => (string) $id],
        );
    }

    /**
     * Deleta um usuário e todos os seus dados relacionados
     * @param UUID $id id do usuário
     * @return bool true se o usuário foi deletado
     * @throws \Exception
     */
    public static function delete(UUID $id): bool
    {
        $database = new Database();
        return $database->query(
            delete: self::$table,
            where: ["id" => (string) $id],
        );
    }
}
