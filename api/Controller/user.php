<?php

namespace Bifrost\Controller;

use Bifrost\Include\Controller;
use Bifrost\Interface\ControllerInterface;
use Bifrost\Attributes\Auth;
use Bifrost\Attributes\Cache;
use Bifrost\Attributes\Details;
use Bifrost\Attributes\Method;
use Bifrost\Attributes\RequiredFields;
use Bifrost\Class\HttpError;
use Bifrost\Class\HttpResponse;
use Bifrost\Core\Request;
use Bifrost\Class\User as UserClass;
use Bifrost\Core\Post;
use Bifrost\DataTypes\Email;
use Bifrost\Enum\HttpStatusCode;
use Bifrost\Enum\Field;
use Bifrost\Model\User as UserModel;
use Bifrost\Class\Role as RoleClass;

class User implements ControllerInterface
{
    use Controller;

    public function index()
    {
        switch ($_SERVER["REQUEST_METHOD"]) {
            case "GET":
                return Request::run("user", "get_users");
            case "POST":
                return Request::run("user", "new_user");
            case "OPTIONS":
                $controller = "user";
                return HttpResponse::returnAttributes("infos", [
                    "list_all" => Request::getOptionsAttributes($controller, "get_users"),
                    "new_user" => Request::getOptionsAttributes($controller, "new_user")
                ]);
            default:
                return HttpError::methodNotAllowed("Method not allowed");
        }
    }

    #[Method("GET")]
    #[Auth("manager", "admin")]
    #[Cache("get_usuario", 60, ["userId"])]
    #[Details([
        "description" => "Lista usuarios do sistema"
    ])]
    public function get_users()
    {
        return HttpResponse::success("Users in system", UserModel::getAll());
    }

    #[Method("POST")]
    #[RequiredFields([
        "name" => Field::STRING,
        "email" => Field::EMAIL,
        "password" => Field::STRING,
    ])]
    #[Details([
        "OptionalFields" => [
            "userName" => Field::STRING
        ],
        "description" => "Cria um novo usuário no sistema"
    ])]
    public function new_user(): HttpError|HttpResponse
    {
        $post = new Post();

        $name = $post->name;
        $userName = empty($post->userName) ? null : $post->userName;
        $email = new Email($post->email);
        $password = $post->password;

        if (UserClass::exists(email: $email, userName: $userName)) {
            return HttpError::badRequest("User already exists");
        }

        $roleClass = new RoleClass();

        $user = UserClass::new(
            name: $name,
            email: $email,
            password: $password,
            userName: $userName,
            role: $roleClass,
        );

        return new HttpResponse(
            statusCode: HttpStatusCode::CREATED,
            message: "User created",
            data: $user
        );
    }
}
