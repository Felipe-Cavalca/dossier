<?php

namespace Bifrost\Model;

use Bifrost\Core\Database;

class Role
{
    private string $table = "roles";
    private Database $database;

    public function __construct()
    {
        $this->database = new Database();
    }

    public function getAll(): array
    {
        return $this->database->select(
            table: $this->table . " r",
            fields: [
                "r.*",
            ]
        );
    }

    public function search(array $conditions)
    {
        return $this->database->select(
            table: $this->table . " r",
            fields: [
                "r.*",
            ],
            where: $conditions
        );
    }

    public function getByCode(string $code): array
    {
        return $this->search(["r.code" => $code])[0] ?? [];
    }
}
