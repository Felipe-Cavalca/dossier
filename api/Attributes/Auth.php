<?php

namespace Bifrost\Attributes;

use Bifrost\Interface\AttributesInterface;
use Bifrost\Core\Session;
use Bifrost\Class\HttpError;
use Bifrost\Core\Database;

#[\Attribute]
class Auth implements AttributesInterface
{
    private Session $session;

    public function __construct(...$p)
    {
        $this->session = new Session();
    }

    public function __destruct() {}

    public function beforeRun(): mixed
    {
        if (!self::isLogged()) {
            return HttpError::unauthorized("Usuário não autenticado");
        }

        $database = new Database();
        $database->setSystemIdentifier(["user_id" => $this->session->id]);

        return null;
    }

    public function afterRun(mixed $return): void {}

    public function getOptions(): array
    {
        return ["Auth" => "Necessário autenticação"];
    }

    public static function isLogged(): bool
    {
        $session = new Session();
        return isset($session->logged) && $session->logged;
    }
}
