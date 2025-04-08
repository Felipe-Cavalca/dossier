<?php

namespace Bifrost\Class;

use Bifrost\DataTypes\UUID;
use Bifrost\Model\Role as RoleModel;

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
            throw HttpError::internalServerError("Role not found", [
                "id" => $id,
                "code" => $code,
            ]);
        }

        $this->id = new UUID($roleData["id"]);
        $this->code = $roleData["code"];
        $this->name = $roleData["name"];
        $this->description = $roleData["description"];
    }
}
