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
        if (!$this->isLogged()) {
            return HttpError::unauthorized("Usuário não autenticado");
        }

        if (!$this->hasRole()) {
            return HttpError::forbidden("Usuário não autorizado");
        }

        $database = new Database();
        $database->setSystemIdentifier(["user_id" => (string) $this->session->user->id]);

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

    public function isLogged(): bool
    {
        return isset($this->session->logged) && $this->session->logged;
    }

    public function hasRole(): bool
    {
        if (empty(self::$roles)) {
            return true;
        }

        return in_array($this->session->user->role->code, self::$roles);
    }
}
