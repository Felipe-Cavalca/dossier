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

class Webdav implements ControllerInterface
{
    use Controller;

    public function auth()
    {
        $usuario = $_POST["username"];
        $password = $_POST["password"];

        if($usuario == "admin" && $password == "admin") {
            return [
                "status" => true,
                "statusCode" => 200,
                "message" => "Login feito com sucesso",
                "data" => [
                    "id" => 1,
                    "name" => "admin",
                ]
            ];
        }

        throw new HttpError("e401", ["Usuário ou senha inválidos"]);
    }
}
