<?php

namespace Bifrost\Controller;

use Bifrost\Interface\ControllerInterface;
use Bifrost\Attributes\Auth;
use Bifrost\Attributes\Cache;
use Bifrost\Attributes\Details;
use Bifrost\Attributes\Method;
use Bifrost\Attributes\OptionalFields;
use Bifrost\Attributes\RequiredFields;
use Bifrost\Attributes\RequiredParams;
use Bifrost\Attributes\OptionalParams;
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
                    return Request::run($this, "get");
                }
                return Request::run($this, "all");
            case "POST":
                return Request::run($this, "new");
            case "PUT":
            case "PATCH":
                return Request::run($this, "update");
            case "DELETE":
                return Request::run($this, "delete");
            case "OPTIONS":
                return HttpResponse::success("infos", [
                    "all" => Request::getOptionsAttributes($this, "all"),
                    "get" => Request::getOptionsAttributes($this, "get"),
                    "new" => Request::getOptionsAttributes($this, "new"),
                    "delete" => Request::getOptionsAttributes($this, "delete"),
                    "update" => Request::getOptionsAttributes($this, "update"),
                ]);
            default:
                return HttpResponse::methodNotAllowed("Method not allowed");
        }
    }

    #[Method("GET")]
    #[Auth("manager", "admin")]
    #[Cache(60)]
    #[Details([
        "description" => "Lista usuarios do sistema"
    ])]
    public function all(): HttpResponse
    {
        return HttpResponse::success("Users in system", UserModel::getAll());
    }

    #[Method("POST")]
    #[RequiredFields([
        "name" => Field::STRING,
        "email" => Field::EMAIL,
        "password" => Field::STRING,
    ])]
    #[OptionalFields([
        "userName" => Field::STRING
    ])]
    #[Details([
        "description" => "Create a new user in the system",
    ])]
    public function new(): HttpResponse
    {
        $post = new Post();

        $name = $post->name;
        $userName = empty($post->userName) ? null : $post->userName;
        $email = new Email($post->email);
        $password = $post->password;

        if (UserClass::exists(email: $email, userName: $userName)) {
            return HttpResponse::badRequest([
                "email" => (string) $email,
                "userName" => $userName
            ], "User already exists");
        }

        $roleClass = new RoleClass();

        $user = UserClass::new(
            name: $name,
            email: $email,
            password: $password,
            userName: $userName,
            role: $roleClass,
        );

        return HttpResponse::created(
            objName: "User",
            data: $user->toArray()
        );
    }

    #[Method("GET")]
    #[RequiredParams([
        "id" => Field::UUID
    ])]
    #[Auth("manager", "admin")]
    #[Cache(60)]
    #[Details([
        "description" => "Lista um usuário do sistema"
    ])]
    public function get(): HttpResponse
    {
        $get = new Get();

        if (!UserClass::exists(id: new UUID($get->id))) {
            return HttpResponse::notFound([
                "id" => $get->id
            ], "User not found");
        }

        $id = new UUID($get->id);
        $user = new UserClass(id: $id);

        return new HttpResponse(
            status: HttpStatusCode::OK,
            message: "User found",
            data: $user->toArray()
        );
    }

    #[Method("PUT", "PATCH")]
    #[RequiredParams([
        "id" => Field::UUID
    ])]
    #[Auth("manager", "admin")]
    #[Details([
        "description" => "Atualiza um usuário do sistema",
        "optionalFields" => [
            "name" => Field::STRING,
            "email" => Field::EMAIL,
            "password" => Field::STRING,
            "userName" => Field::STRING,
            "roleCode" => Field::STRING
        ]
    ])]
    public function update()
    {
        $get = new Get();

        if (!UserClass::exists(id: new UUID($get->id))) {
            return HttpResponse::notFound([], "User not found");
        }

        $id = new UUID($get->id);
        $post = new Post();
        $user = new UserClass(id: $id);

        if (!empty($post->name)) {
            $user->name = $post->name;
        }
        if (!empty($post->email)) {
            $user->email = new Email($post->email);
        }
        if (!empty($post->password)) {
            $user->password = $post->password;
        }
        if (!empty($post->userName)) {
            $user->userName = $post->userName;
        }
        if (!empty($post->roleCode)) {
            $user->role = new RoleClass(code: $post->roleCode);
        }

        //Apaga obj usuario para atualizar no banco
        unset($user);

        return new HttpResponse(
            status: HttpStatusCode::OK,
            message: "User updated",
            data: []
        );
    }

    #[Method("DELETE")]
    #[RequiredParams([
        "id" => Field::UUID
    ])]
    #[Auth("manager", "admin")]
    #[Details([
        "description" => "Deleta um usuário e todos os seus dados relacionados"
    ])]
    public function delete(): HttpResponse
    {
        $get = new Get();

        if (!UserClass::exists(id: new UUID($get->id))) {
            return HttpResponse::notFound([], "User not found");
        }

        $id = new UUID($get->id);

        if (UserModel::delete($id)) {
            return new HttpResponse(
                status: HttpStatusCode::OK,
                message: "User deleted",
                data: []
            );
        }

        return HttpResponse::internalServerError([], "Error deleting user");
    }
}
