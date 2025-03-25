<?php

namespace Bifrost\Controller;

use Bifrost\Attributes\Details;
use Bifrost\Attributes\Method;
use Bifrost\Attributes\RequiredFields;
use Bifrost\Class\HttpError;
use Bifrost\Class\HttpResponse;
use Bifrost\Class\User;
use Bifrost\Enum\Field;
use Bifrost\Include\Controller;
use Bifrost\Interface\ControllerInterface;
use Bifrost\Core\Post;
use Bifrost\Core\Session;
use Bifrost\DataTypes\Email;

class Auth implements ControllerInterface
{
    use Controller;

    #[Method("POST")]
    #[RequiredFields([
        "email" => Field::EMAIL,
        "password" => Field::STRING
    ])]
    #[Details([
        "description" => "Realiza o login do usuário"
    ])]
    public function login()
    {
        $post = new Post();
        $user = new User(email: new Email($post->email));

        if (!isset($user->email) || !$user->validatePassword($post->password)) {
            return HttpError::unauthorized("Usuário ou senha invalidos");
        }

        $session = new Session();
        $session->logged = true;
        $session->userId = $user->id;
        return HttpResponse::success("Usuário logado com sucesso", $user);
    }

    #[Method("GET")]
    #[Auth("user", "manager", "admin")]
    #[Details([
        "description" => "Valida se o usuário está logado"
    ])]
    public function validate()
    {
        return HttpResponse::success("Usuário logado com sucesso");
    }

    #[Details([
        "description" => "Realiza o logout do usuário"
    ])]
    public function logout()
    {
        $session = new Session();
        $session->destroy();
        return HttpResponse::success("Usuário deslogado com sucesso");
    }
}
