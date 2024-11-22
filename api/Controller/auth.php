<?php

namespace Bifrost\Controller;

use Bifrost\Attributes\Details;
use Bifrost\Attributes\Method;
use Bifrost\Attributes\RequiredFields;
use Bifrost\Class\HttpError;
use Bifrost\Class\HttpResponse;
use Bifrost\Class\User;
use Bifrost\Enum\ValidateField;
use Bifrost\Include\Controller;
use Bifrost\Interface\ControllerInterface;
use Bifrost\Core\Post;

class Auth implements ControllerInterface
{
    use Controller;

    #[Method("POST")]
    #[RequiredFields([
        "email" => ValidateField::EMAIL,
        "password" => ValidateField::STRING
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

        return HttpResponse::success("Usuário logado com sucesso", $user);
    }
}
