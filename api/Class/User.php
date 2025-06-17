<?php

namespace Bifrost\Class;

use JsonSerializable;
use Bifrost\DataTypes\Email;
use Bifrost\DataTypes\UUID;
use Bifrost\Model\User as UserModel;
use Bifrost\Class\Role as Role;
use Bifrost\Class\EntityDuplicateException;

/**
 * @property UUID id
 * @property string name
 * @property string userName
 * @property Email email
 * @property string password
 * @property Role role
 */
class User implements JsonSerializable
{
    public ?UUID $id = null;
    private ?Email $email = null;
    private array $data = [];
    private ?Role $roleClass = null;
    private array $updateFields = [];

    public function __construct(
        ?UUID $id = null,
        ?Email $email = null,
        array $allData = [],
    ) {
        if (!empty($id)) {
            $this->id = $id;
        }
        if (!empty($email)) {
            $this->email = $email;
        }

        if (!empty($allData)) {
            $this->data = $allData;
        }
    }

    /**
     * Atualiza os dados do usuário no banco de dados
     */
    public function __destruct()
    {
        if (!empty($this->updateFields)) {
            UserModel::update(
                id: $this->id,
                data: $this->updateFields,
            );
        }
    }

    /**
     * Retorna os dados do usuário
     * @param string $property nome da propriedade
     * @return mixed valor da prorpiedade
     */
    public function __get(string $property): mixed
    {
        switch ($property) {
            case "role":
                return $this->getRole();
            case "id":
            case "email":
                if (!empty($this->$property)) {
                    return $this->$property;
                }
            case "role_id":
            case "name":
            case "userName":
            case "password":
                if (empty($this->data)) {
                    $this->data = $this->getData();
                }
                return $this->data[$property] ?? null;
        }

        return null;
    }

    public function __set(string $property, mixed $value): void
    {
        switch ($property) {
            case "email":
                if ($value instanceof Email) {
                    $this->email = $value;
                    $this->updateFields["email"] = (string) $value;
                }
                break;
            case "role":
                if ($value instanceof Role) {
                    $this->roleClass = $value;
                    $this->updateFields["role_id"] = (string) $value->id;
                }
                break;
            case "password":
                $this->data[$property] = password_hash($value, PASSWORD_DEFAULT);
                $this->updateFields[$property] = $this->data[$property];
                break;
            case "name":
            case "userName":
                $this->data[$property] = $value;
                $this->updateFields[$property] = $value;
                break;
        }
    }

    public function __toString(): string
    {
        return json_encode(
            $this->toArray(),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    private function getData(): array
    {
        if (!empty($this->id)) {
            $data = UserModel::getById($this->id);
            $this->email = new Email($data["email"]);
            return $data;
        }
        if (!empty($this->email)) {
            $data = UserModel::getByEmail($this->email);
            $this->id = new UUID($data["id"]);
            return $data;
        }
        return [];
    }

    /**
     * Converte o objeto User em um array associativo.
     * @return array Associative array com os dados do usuário.
     */
    public function toArray(): array
    {
        return [
            "id" => (string) $this->__get("id"),
            "name" => $this->name,
            "userName" => $this->userName,
            "email" => (string) $this->__get("email"),
            "role" => $this->role,
        ];
    }

    /**
     * Implementa a interface JsonSerializable para permitir a serialização do objeto em JSON.
     * @return array Associative array com os dados do usuário para serialização em JSON.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private function getRole(): Role
    {
        if (empty($this->roleClass)) {
            $this->roleClass = new Role(new UUID($this->role_id));
        }
        return $this->roleClass;
    }

    /**
     * Valida se a senha passada é a mesma do usuário
     * @param string $password senha a ser validada
     * @return bool true se a senha for válida, false caso contrário
     */
    public function validatePassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    public static function exists(?string $userName = null, ?Email $email = null, ?UUID $id = null): bool
    {
        if ($id !== null) {
            $conditions['id'] = (string) $id;
        }

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

        $dataUser = UserModel::new(
            name: $name,
            email: $email,
            password: $password,
            userName: $userName,
            role: $role
        );

        return new self(allData: $dataUser);
    }

    /**
     * Verifica se o usuário tem a role passada
     * @param array $roles nome da role a ser verificada
     * @return bool usuario tem a role
     */
    public function hasRole(array $roles): bool
    {
        return in_array($this->role->code, $roles);
    }
}
