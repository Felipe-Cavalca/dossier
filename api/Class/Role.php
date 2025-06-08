<?php

namespace Bifrost\Class;

use Bifrost\DataTypes\UUID;
use Bifrost\Model\Role as RoleModel;
use Bifrost\Class\HttpResponse;
use Bifrost\Core\AppError;

class Role
{
    public UUID $id;
    public string $code;
    public string $name;
    public string $description;
    private string $defaultRole = "user";

    public function __construct(
        ?UUID $id = null,
        ?string $code = null,
    ) {
        if ($id !== null) {
            $roleData = RoleModel::getById($id);
        } elseif ($code !== null) {
            $roleData = RoleModel::getByCode($code);
        } elseif (empty($id) && empty($code)) {
            $roleData = RoleModel::getByCode($this->defaultRole);
        }

        if (empty($roleData)) {
            throw new AppError(
                HttpResponse::internalServerError(
                    errors: [
                        "id" => $id,
                        "code" => $code,
                    ],
                    message: "Role not found"
                )
            );
        }

        $this->id = new UUID($roleData["id"]);
        $this->code = $roleData["code"];
        $this->name = $roleData["name"];
        $this->description = $roleData["description"];
    }

    public function __toString()
    {
        return json_encode([
            "id" => (string) $this->id,
            "code" => $this->code,
            "name" => $this->name,
            "description" => $this->description,
        ]);
    }
}
