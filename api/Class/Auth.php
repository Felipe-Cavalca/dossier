<?php

namespace Bifrost\Class;

use Bifrost\Core\Session;
use Bifrost\Class\User;
use Bifrost\Core\Database;
use Bifrost\DataTypes\Email;
use Bifrost\Model\User as ModelUser;

class Auth
{
    /**
     * Valida se o usuário está logado
     * função verifica se o logged é true na sessão
     * @return bool
     */
    public static function isLogged(): bool
    {
        $session = new Session();

        if (!$session->logged) {
            return false;
        }

        if (!ModelUser::exists(["id" => (string) $session->userId])) {
            return false;
        }

        return $session->logged ?? false;
    }

    /**
     * Verifica se o usuário está autenticado
     * @param Email $email
     * @param string $password
     * @return bool Usuario autenticado e logado
     */
    public static function autenticate(Email $email, string $password): bool
    {
        $user = new User(email: $email);

        if (!$user->validatePassword($password)) {
            return false;
        }

        $session = new Session();
        $session->logged = true;
        $session->userId = $user->id;

        return true;
    }

    /**
     * Logout do usuário
     * @return void
     */
    public static function logout(): void
    {
        $session = new Session();
        $session->destroy();
    }

    /**
     * Verifica se o usuário tem a role passada
     * @param array $role
     * @return bool
     */
    public static function hasRole(array $role): bool
    {
        $user = self::getCourentUser();

        if (!($user instanceof User)) {
            return false;
        }

        return $user->hasRole($role);
    }

    /**
     * Coloca o id do usuário logado como identificados no banco de dados para os logs
     * @return void
     */
    public static function setIdentifierOnDatabase(): void
    {
        $database = new Database();
        $user = self::getCourentUser();
        $database->setSystemIdentifier(["user_id" => (string) $user->id]);
    }

    /**
     * Retorna o usuário logado na session
     * @return User Usuário autenticado.
     */
    public static function getCourentUser(): User
    {
        $session = new Session();
        return new User($session->userId);
    }
}
