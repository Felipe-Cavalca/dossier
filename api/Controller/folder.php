<?php

namespace Bifrost\Controller;

use Bifrost\Attributes\Auth;
use Bifrost\Attributes\Cache;
use Bifrost\Attributes\Details;
use Bifrost\Attributes\Method;
use Bifrost\Attributes\RequiredFields;
use Bifrost\Attributes\OptionalFields;
use Bifrost\Attributes\OptionalParams;
use Bifrost\Class\Auth as ClassAuth;
use Bifrost\Class\HttpResponse;
use Bifrost\Class\Folder as FolderClass;
use Bifrost\Core\Post;
use Bifrost\Core\Request;
use Bifrost\DataTypes\FolderName;
use Bifrost\DataTypes\UUID;
use Bifrost\Enum\Field;
use Bifrost\Enum\HttpStatusCode;
use Bifrost\Interface\ControllerInterface;
use Bifrost\Model\Folder as ModelFolder;

class Folder implements ControllerInterface
{

    public function index()
    {
        $controller = "folder";
        switch ($_SERVER["REQUEST_METHOD"]) {
            case "GET":
                return Request::run($controller, "list");
            case "POST":
                return Request::run($controller, "new");
            case "OPTIONS":
                return HttpResponse::returnAttributes("infos", [
                    "list" => Request::getOptionsAttributes($controller, "list"),
                    "all" => Request::getOptionsAttributes($controller, "all"),
                    "new" => Request::getOptionsAttributes($controller, "new")
                ]);
            default:
                return HttpResponse::methodNotAllowed("Method not allowed");
        }
    }

    #[Method("POST")]
    #[RequiredFields([
        "name" => Field::FILE_PATH,
    ])]
    #[Auth("user", "manager", "admin")]
    #[OptionalFields([
        "parent_id" => Field::UUID
    ])]
    #[Details([
        "Description" => "Cria um novo usuário no sistema"
    ])]
    public function new()
    {
        $user = ClassAuth::getCourentUser();
        $post = new Post();

        $name = new FolderName($post->name);
        $parent = null;

        if ($post->parent_id) {
            $id = new UUID($post->parent_id);
            if (!FolderClass::validId($id)) {
                return HttpResponse::badRequest(
                    errors: [
                        "fieldName" => "parent_id",
                        "fieldValue" => (string) $post->parent_id
                    ],
                    message: "Parent ID is not valid"
                );
            }

            $parent = new FolderClass(id: $id);
        }

        if (FolderClass::exists(name: $name, reference: $parent ?? $user)) {
            return HttpResponse::badRequest([], "Folder already exists");
        }

        $folder = FolderClass::new(
            user: $user,
            name: $name,
            parent: $parent
        );

        return new HttpResponse(
            statusCode: HttpStatusCode::CREATED,
            message: "Folder created",
            data: (string) $folder
        );
    }

    #[Auth("user", "manager", "admin")]
    #[Cache("list-user", 60)]
    #[Details(["Description" => "Retorna uma lista de pastas do usuário autenticado"])]
    #[Method("GET")]
    public function list(): HttpResponse
    {
        return new HttpResponse(
            statusCode: HttpStatusCode::OK,
            message: "Folders found",
            data: ModelFolder::list(user: ClassAuth::getCourentUser())
        );
    }

    #[Auth("manager", "admin")]
    #[Cache("list-all", 60)]
    #[Details(["Description" => "Retorna uma lista de pastas de todos os usuários"])]
    #[Method("GET")]
    public function all(): HttpResponse
    {
        return new HttpResponse(
            statusCode: HttpStatusCode::OK,
            message: "Folders found",
            data: ModelFolder::list()
        );
    }
}
