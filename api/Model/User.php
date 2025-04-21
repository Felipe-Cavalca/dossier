<?php

namespace Bifrost\Model;

use Bifrost\Core\Database;
use Bifrost\DataTypes\Email;
use Bifrost\DataTypes\UUID;
use Bifrost\Class\Role as Role;

class User
{
    private static string $table = "users";

    public static function getById(UUID $id): array
    {
        return self::search(["u.id" => (string) $id])[0] ?? [];
    }

    public static function getByEmail(Email $email): array
    {
        return self::search(["u.email" => (string) $email])[0] ?? [];
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
                "u.username",
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

    public static function search(array $conditions): array
    {
        $database = new Database();
        return $database->select(
            table: self::$table . " u",
            fields: [
                "u.*"
            ],
            where: $conditions
        );
    }

    public static function getAllData(UUID $userId): array
    {
        $database = new Database();
        return $database->select(
            table: self::$table . " u",
            fields: [
                "u.id",
                "r.name AS role",
                "u.name",
                "u.username",
                "u.email",
                "ulc.changed AS created",
                "COALESCE(ulu.changed, ulc.changed) AS updated",
            ],
            join: [
                "JOIN users_log ulc ON ulc.original_id = u.id AND ulc.action = 'INSERT'",
                "LEFT JOIN users_log ulu ON ulu.original_id = u.id AND ulu.action = 'UPDATE'",
                "JOIN roles r ON r.id = u.role_id",
            ],
            where: ["u.id" => (string) $userId]
        )[0] ?? [];
    }

    public static function new(
        string $name,
        Email $email,
        string $password,
        Role $role,
        ?string $userName = null,
    ): array {
        $database = new Database();

        $userData = [
            "name" => $name,
            "email" => (string) $email,
            "password" => password_hash($password, PASSWORD_DEFAULT),
            "username" => $userName,
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
        static $localCache = [];

        $key = md5(json_encode($conditions));

        if (array_key_exists($key, $localCache)) {
            return $localCache[$key];
        }

        $database = new Database();
        $result = $database->select(
            table: self::$table,
            fields: ["*"],
            where: $conditions,
            limit: "1",
        );

        $localCache[$key] = !empty($result);
        return $localCache[$key];
    }
}
