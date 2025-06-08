<?php

namespace Bifrost\Controller;

use Bifrost\Attributes\Details;
use Bifrost\Attributes\Method;
use Bifrost\Attributes\RequiredFields;
use Bifrost\Attributes\Auth as AuthAttribute;
use Bifrost\Class\Auth as ClassAuth;
use Bifrost\Class\HttpResponse;
use Bifrost\Enum\Field;
use Bifrost\Interface\ControllerInterface;
use Bifrost\Core\Post;
use Bifrost\DataTypes\Email;

class Auth implements ControllerInterface
{

    public function index()
    {
        return HttpResponse::success("API Auth");
    }

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
        $email = new Email($post->email);
        $password = $post->password;

        return ClassAuth::autenticate($email, $password) ?
            HttpResponse::success("Usuário logado com sucesso") :
            HttpResponse::unauthorized("Usuário ou senha invalidos");
    }

    #[Method("GET")]
    #[AuthAttribute("user", "manager", "admin")]
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
        ClassAuth::logout();
        return HttpResponse::success("Usuário deslogado com sucesso");
    }
}
