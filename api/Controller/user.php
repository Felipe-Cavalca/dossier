<?php

namespace Bifrost\Controller;

use Bifrost\Interface\ControllerInterface;
use Bifrost\Attributes\Auth;
use Bifrost\Attributes\Cache;
use Bifrost\Attributes\Details;
use Bifrost\Attributes\Method;
use Bifrost\Attributes\RequiredFields;
use Bifrost\Attributes\RequiredParams;
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
use Bifrost\Core\Get;
use Bifrost\DataTypes\UUID;

class User implements ControllerInterface
{

    public function index()
    {
        switch ($_SERVER["REQUEST_METHOD"]) {
            case "GET":
                $get = new Get();

                if (isset($get->id)) {
                    return Request::run("user", "get");
                }

                return Request::run("user", "all");
            case "POST":
                return Request::run("user", "new");
            case "OPTIONS":
                $controller = "user";
                return HttpResponse::returnAttributes("infos", [
                    "all" => Request::getOptionsAttributes($controller, "all"),
                    "get" => Request::getOptionsAttributes($controller, "get"),
                    "new" => Request::getOptionsAttributes($controller, "new")
                ]);
            default:
                return HttpError::methodNotAllowed("Method not allowed");
        }
    }

    #[Method("GET")]
    #[Auth("manager", "admin")]
    #[Cache("get_usuario", 60)]
    #[Details([
        "description" => "Lista usuarios do sistema"
    ])]
    public function all(): array
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
    public function new(): HttpError|HttpResponse
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

    #[Method("GET")]
    #[RequiredParams([
        "id" => Field::UUID
    ])]
    #[Auth("manager", "admin")]
    #[Cache("get_usuario", 60)]
    #[Details([
        "description" => "Lista um usuário do sistema"
    ])]
    public function get(): HttpError|HttpResponse
    {
        $get = new Get();

        if (!UserModel::exists(["id" => $get->id])) {
            return HttpError::notFound("User not found");
        }

        $id = new UUID($get->id);
        $user = new UserClass(id: $id);

        return new HttpResponse(
            statusCode: HttpStatusCode::OK,
            message: "User found",
            data: $user
        );
    }
}
