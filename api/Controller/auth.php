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
use Bifrost\DataTypes\Password;

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
        $password = new Password($post->password);

        return
            ClassAuth::autenticate(email: $email, password: $password) ?
            HttpResponse::success(
                message: "user logged in successfully",
                data: ClassAuth::getCourentUser()->toArray()
            ) :
            HttpResponse::unauthorized(
                message: "Invalid email or password"
            );
    }

    #[Method("GET")]
    #[AuthAttribute("user", "manager", "admin")]
    #[Details([
        "description" => "Valida se o usuário está logado"
    ])]
    public function validate()
    {
        return HttpResponse::success("user is logged in");
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
