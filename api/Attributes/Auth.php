<?php

namespace Bifrost\Attributes;

use Bifrost\Interface\AttributesInterface;
use Bifrost\Core\Session;
use Bifrost\Class\HttpError;
use Bifrost\Core\Database;
use Bifrost\Model\User as ModelUser;

#[\Attribute]
class Auth implements AttributesInterface
{
    private Session $session;
    private static array $roles;

    public function __construct(...$p)
    {
        $this->session = new Session();
        self::$roles = $p;
    }

    public function __destruct() {}

    public function beforeRun(): mixed
    {
        if (!self::isLogged()) {
            return HttpError::unauthorized("Usuário não autenticado");
        }

        if (!self::hasRole()) {
            return HttpError::forbidden("Usuário não autorizado");
        }

        $database = new Database();
        $database->setSystemIdentifier(["user_id" => $this->session->userId]);

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

    public static function isLogged(): bool
    {
        $session = new Session();
        return isset($session->logged) && $session->logged;
    }

    public static function hasRole(): bool
    {
        if (empty(self::$roles)) {
            return true;
        }

        $session = new Session();
        $user = (new ModelUser())->getById($session->userId);
        return in_array($user["role"], self::$roles);
    }
}
