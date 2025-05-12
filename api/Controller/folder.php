<?php

namespace Bifrost\Controller;

use Bifrost\Attributes\Auth;
use Bifrost\Attributes\Cache;
use Bifrost\Attributes\Details;
use Bifrost\Attributes\Method;
use Bifrost\Attributes\RequiredFields;
use Bifrost\Attributes\RequiredParams;
use Bifrost\Class\HttpResponse;
use Bifrost\Class\HttpError;
use Bifrost\Class\Folder as FolderClass;
use Bifrost\Core\Post;
use Bifrost\Core\Request;
use Bifrost\Core\Session;
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
            case "POST":
                return Request::run($controller, "new");
            case "OPTIONS":
                return HttpResponse::returnAttributes("infos", [
                    "new" => Request::getOptionsAttributes($controller, "new")
                ]);
            default:
                return HttpError::methodNotAllowed("Method not allowed");
        }
    }

    #[Method("POST")]
    #[RequiredFields([
        "name" => Field::FILE_PATH,
    ])]
    #[Auth("user", "manager", "admin")]
    #[Details([
        "OptionalFields" => [
            "parent_id" => Field::UUID
        ],
        "description" => "Cria um novo usuário no sistema"
    ])]
    public function new()
    {
        $post = new Post();
        $session = new Session();

        $name = new FolderName($post->name);
        $user = $session->user;
        $parent = null;

        if ($post->parent_id) {
            $id = new UUID($post->parent_id);
            if (!ModelFolder::validId(id: $id)) {
                return HttpError::badRequest("Parent ID is not valid", [
                    "fieldName" => "parent_id",
                    "fieldValue" => (string) $post->parent_id
                ]);
            }

            $parent = new FolderClass(id: new UUID($post->parent_id));
        }

        if (FolderClass::exists(name: $name, reference: $parent ?? $user)) {
            return HttpError::badRequest("Folder already exists");
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


    #[Method("GET")]
    #[RequiredParams([
        "user_id" => FILTER_VALIDATE_INT
    ])]
    #[Cache("folder-list", 5)]
    public function details(): array
    {
        if (!empty($_GET["id"])) {
            $folder = new FolderClass((int) $_GET["id"], $_GET["user_id"]);
        } elseif (!empty($_GET["path"])) {
            $folder = new FolderClass((string) $_GET["path"], $_GET["user_id"]);
        } else {
            throw HttpError::badRequest("Nenhum dos campos encontrados", [
                "fieldName" => ["id", "path"],
            ]);
        }

        if (!isset($folder->id)) {
            throw HttpError::notFound("A pasta não existe");
        }

        return HttpResponse::success(
            message: "Listagem de pastas",
            data: $folder
        );
    }

    #[Method("GET")]
    #[RequiredParams([
        "user_id" => FILTER_VALIDATE_INT,
    ])]
    // #[Cache("folder-list", 5)]
    public function content(): array
    {
        if (!empty($_GET["id"])) {
            $folder = new FolderClass((int) $_GET["id"], (int) $_GET["user_id"]);
        } elseif (!empty($_GET["path"])) {
            $folder = new FolderClass((string) $_GET["path"], (int) $_GET["user_id"]);
        } else {
            $folder = new FolderClass(null, (int) $_GET["user_id"]);
        }

        $content = $folder->getContent();

        if (empty($content)) {
            throw HttpError::notFound("Nenhum conteudo encontrado", [
                "folder" => $folder
            ]);
        }

        return HttpResponse::success(
            message: "Listagem de Conteudo",
            data: $content
        );
    }
}
