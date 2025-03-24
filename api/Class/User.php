<?php

namespace Bifrost\Class;

use Bifrost\DataTypes\Email;
use Bifrost\DataTypes\UUID;
use Bifrost\Model\User as UserModel;

class User
{
    protected UserModel $userModel;
    private array $userData = [];

    public function __construct(
        ?UUID $id = null,
        ?Email $email = null,
    ) {
        $this->userModel = new UserModel();

        if ($id !== null) {
            $userData = $this->userModel->getById($id);
        } elseif ($email !== null) {
            $userData = $this->userModel->getByEmail($email);
        }

        if (empty($userData)) {
            return;
        }

        $this->userData = $userData;
    }

    public function __get(string $property): mixed
    {
        return $this->userData[$property] ?? null;
    }

    public function __set(string $property, mixed $value): void
    {
        $this->userData[$property] = $value;
    }

    public function __isset(string $property): bool
    {
        return isset($this->userData[$property]);
    }

    public function __toString(): string
    {
        return json_encode($this->userModel->print(new UUID((string) $this->id)));
    }

    public function validatePassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }
}
