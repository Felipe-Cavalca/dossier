<?php

namespace Bifrost\Attributes;

use Bifrost\Class\Auth as ClassAuth;
use Bifrost\Interface\AttributesInterface;
use Bifrost\Class\HttpError;
use Bifrost\DataTypes\Email;

#[\Attribute]
class Auth implements AttributesInterface
{
    private static array $roles;

    public function __construct(...$p)
    {
        self::$roles = $p;
    }

    public function __destruct() {}

    public function beforeRun(): mixed
    {
        if (isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
            $email = new Email($_SERVER['PHP_AUTH_USER']);
            $password = $_SERVER['PHP_AUTH_PW'];

            if (!ClassAuth::autenticate($email, $password)) {
                return HttpError::unauthorized("Credenciais inválidas");
            }
        }

        if (!ClassAuth::isLogged()) {
            return HttpError::unauthorized("Usuário não autenticado");
        }

        if (!ClassAuth::hasRole(self::$roles)) {
            return HttpError::forbidden("Usuário não autorizado");
        }

        ClassAuth::setIdentifierOnDatabase();

        return null;
    }

    public function afterRun(mixed $return): void {}

    public function getOptions(): array
    {
        return [
            "Auth" => [
                "Description" => "Necessário autenticação",
                "Roles" => self::$roles
            ]
        ];
    }
}
