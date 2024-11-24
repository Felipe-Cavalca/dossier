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
use Bifrost\Core\Session;
use Bifrost\Class\User as ClassUser;
use Bifrost\Core\Database;
use Bifrost\Core\Post;
use Bifrost\Enum\HttpStatusCode;
use Bifrost\Enum\ValidateField;
use Bifrost\Model\User as ModelUser;
use Bifrost\Model\Role as ModelRole;

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
    #[Auth("user", "manager", "admin")]
    #[Cache("get_usuario", 60, ["userId"])]
    #[Details([
        "description" => "Lista usuarios do sistema"
    ])]
    public function get_users()
    {
        $model = new ModelUser();
        return HttpResponse::success("Users in system", $model->getAll());
    }

    #[Method("POST")]
    #[Auth("manager", "admin")]
    #[RequiredFields([
        "name" => ValidateField::STRING,
        "userName" => ValidateField::STRING,
        "email" => ValidateField::EMAIL,
        "password" => ValidateField::STRING,
        "role" => ValidateField::STRING
    ])]
    #[Details([
        "description" => "Cria um novo usuário no sistema"
    ])]
    public function new_user()
    {
        $post = new Post();
        $roleModel = new ModelRole();
        $userModel = new ModelUser();
        $database = new Database();

        $role = $roleModel->getByCode($post->role);
        if (empty($role)) {
            return HttpError::badRequest("Role inválida", [
                "role" => "Role inválida"
            ]);
        }

        $userValidate = $userModel->search(
            ["or" => [
                "userName" => $post->userName,
                "email" => $post->email,
            ]]
        );
        if (!empty($userValidate)) {
            $fieldsToCheck = [
                "email" => "Já existe um usuário com o email cadastrado",
                "userName" => "Já existe um usuário com o userName cadastrado"
            ];
            foreach ($fieldsToCheck as $field => $errorMessage) {
                $fieldDatabase = strtolower($field);
                if (!empty($userValidate[0][$fieldDatabase]) && $userValidate[0][$fieldDatabase] == $post->$field) {
                    return HttpError::badRequest($errorMessage);
                }
            }
        }

        $id = $database->insert(
            table: "users",
            data: [
                "name" => $post->name,
                "userName" => $post->userName,
                "email" => $post->email,
                "password" => password_hash($post->password, PASSWORD_DEFAULT),
                "role_id" => $role["id"]
            ],
            returning: "id"
        );
        $user = new ClassUser(id: $id);

        return new HttpResponse(
            statusCode: HttpStatusCode::CREATED,
            message: "User created",
            data: $user
        );
    }
}
