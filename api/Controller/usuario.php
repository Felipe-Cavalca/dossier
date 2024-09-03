<?php

namespace Bifrost\Controller;

use Bifrost\Interface\ControllerInterface;
use Bifrost\Include\Controller;
use Bifrost\Attributes\Method;
use Bifrost\Attributes\RequiredFields;
use Bifrost\Attributes\RequiredParams;
use Bifrost\Attributes\Cache;
use Bifrost\Core\Database;
use Bifrost\Class\HttpError;

class Usuario implements ControllerInterface
{
    use Controller;

    #[Method(["POST"])]
    #[RequiredFields([
        "email" => FILTER_VALIDATE_EMAIL,
        "password",
    ])]
    public function login()
    {
        $database = new Database();
        $email = $_POST["email"];
        $password = $_POST["password"];

        $user = $database->list(
            "SELECT * FROM users WHERE email = :email AND password = :password",
            [":email" => $email, ":password" => $password]
        );

        if (count($user) == 1) {
            return [
                "status" => true,
                "statusCode" => 200,
                "message" => "Login feito com sucesso",
                "data" => $user[0]
            ];
        }

        throw new HttpError("e401", ["Usuário ou senha inválidos"]);
    }
}
