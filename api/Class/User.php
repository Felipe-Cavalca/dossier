<?php

namespace Bifrost\Class;

use Bifrost\Include\DatabaseProperties;
use Bifrost\Model\User as UserModel;

class User
{
    use DatabaseProperties;

    protected UserModel $userModel;
    private string $password;

    public function __construct(
        string $id = null,
        string $email = null,
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

        $this->password = $userData["password"];
        unset($userData["password"]);

        $this->setDatabaseProperties($userData);
    }

    public function validatePassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }
}
