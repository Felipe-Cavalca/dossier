<?php

namespace Bifrost\Class;

use Bifrost\DataTypes\Email;
use Bifrost\DataTypes\UUID;
use Bifrost\Model\User as UserModel;
use Bifrost\Class\Role as Role;
use Bifrost\Class\EntityNotFoundException;
use Bifrost\Class\EntityDuplicateException;

class User
{
    public UUID $id;
    public string $name;
    public string $username;
    public Email $email;
    public string $password;
    private UUID $roleId;
    private ?Role $roleClass = null;

    public function __construct(
        ?UUID $id = null,
        ?Email $email = null,
        array $allData = [],
    ) {
        if ($allData !== null && !empty($allData)) {
            $userData = $allData;
        } elseif ($id !== null) {
            $userData = UserModel::getById($id);
        } elseif ($email !== null) {
            $userData = UserModel::getByEmail($email);
        }

        if (empty($userData)) {
            throw new EntityNotFoundException("User", [
                "id" => $id,
                "email" => $email,
            ]);
        }

        $this->id = new UUID($userData["id"]);
        $this->name = $userData["name"];
        $this->username = $userData["username"];
        $this->email = new Email($userData["email"]);
        $this->password = $userData["password"];
        $this->roleId = new UUID($userData["role_id"]);
    }

    public function __get(string $property): mixed
    {
        switch ($property) {
            case "role":
                return $this->getRole();
        }

        return null;
    }

    public function __toString(): string
    {
        return json_encode([
            "id" => (string) $this->id,
            "name" => $this->name,
            "userName" => $this->username,
            "email" => (string) $this->email,
            "role" => (string) $this->role->code,
        ]);
    }

    private function getRole(): Role
    {
        if (empty($this->roleClass)) {
            $this->roleClass = new Role($this->roleId);
        }
        return $this->roleClass;
    }

    public function validatePassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    public static function exists(?string $userName = null, ?Email $email = null): bool
    {
        if ($userName !== null && $email !== null) {
            $conditions["or"] = [
                "userName" => $userName,
                "email"    => (string) $email,
            ];
        } elseif ($email !== null) {
            $conditions["email"] = (string) $email;
        } elseif ($userName !== null) {
            $conditions["userName"] = $userName;
        }

        if (empty($conditions)) {
            return false;
        }

        return UserModel::exists($conditions);
    }

    public static function new(
        string $name,
        Email $email,
        string $password,
        Role $role,
        ?string $userName = null,
    ): self {

        if (self::exists(email: $email, userName: $userName)) {
            throw new EntityDuplicateException("User");
        }

        $dataUser = UserModel::newUser(
            name: $name,
            email: $email,
            password: $password,
            userName: $userName,
            role: $role
        );

        return new self(allData: $dataUser);
    }
}
