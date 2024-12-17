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
        $user = new User(email: $post->email);

        if (!isset($user->email) || !$user->validatePassword($post->password)) {
            return HttpError::unauthorized("Usuário ou senha invalidos");
        }

        $session = new Session();
        $session->logged = true;
        $session->userId = $user->id;
        return HttpResponse::success("Usuário logado com sucesso", $user);
    }
}
