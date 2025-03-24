<?php

namespace Bifrost\Model;

use Bifrost\Core\Database;
use Bifrost\DataTypes\Email;
use Bifrost\DataTypes\UUID;

class User
{
    private string $table = "users";
    private Database $database;

    public function __construct()
    {
        $this->database = new Database();
    }

    public function getById(UUID $id): array
    {
        return $this->search(["u.id" => $id])[0] ?? [];
    }

    public function getByEmail(Email $email): array
    {
        return $this->search(["u.email" => $email])[0] ?? [];
    }

    public function getAll(): array
    {
        return $this->database->select(
            table: $this->table . " u",
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

    public function search(array $conditions): array
    {
        return $this->database->select(
            table: $this->table . " u",
            fields: [
                "u.*",
                "r.code AS role",
            ],
            join: [
                "JOIN roles r ON r.id = u.role_id",
            ],
            where: $conditions
        );
    }

    public function print(UUID $userId): array
    {
        return $this->database->select(
            table: $this->table . " u",
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
            where: ["u.id" => $userId]
        )[0] ?? [];
    }
}
