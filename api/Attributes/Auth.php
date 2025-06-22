<?php

namespace Bifrost\Attributes;

use Bifrost\Class\Auth as ClassAuth;
use Bifrost\Interface\AttributesInterface;
use Bifrost\Class\HttpResponse;
use Bifrost\DataTypes\Email;
use Bifrost\DataTypes\Password;

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
            $password = new Password($_SERVER['PHP_AUTH_PW']);

            if (!ClassAuth::autenticate($email, $password)) {
                return HttpResponse::unauthorized("Invalid credentials");
            }
        }

        if (!ClassAuth::isLogged()) {
            return HttpResponse::unauthorized("User not authenticated");
        }

        if (!ClassAuth::hasRole(self::$roles)) {
            return HttpResponse::forbidden("User not authorized");
        }

        ClassAuth::setIdentifierOnDatabase();

        return null;
    }

    public function afterRun(mixed $return): void {}

    public function getOptions(): array
    {
        return [
            "auth" => [
                "description" => "Authentication required",
                "roles" => self::$roles
            ]
        ];
    }
}
