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
        switch ($_SERVER["REQUEST_METHOD"]) {
            case "GET":
                return Request::run($this, "list");
            case "POST":
                return Request::run($this, "new");
            case "OPTIONS":
                return HttpResponse::success("infos", [
                    "list" => Request::getOptionsAttributes($this, "list"),
                    "all" => Request::getOptionsAttributes($this, "all"),
                    "new" => Request::getOptionsAttributes($this, "new")
                ]);
            default:
                return HttpResponse::methodNotAllowed("Method not allowed");
        }
    }

    #[Auth("user", "manager", "admin")]
    #[Method("POST")]
    #[RequiredFields(["name" => Field::FILE_PATH])]
    #[OptionalFields(["parent_id" => Field::UUID])]
    #[Details(["description" => "Creates a new folder"])]
    public function new(): HttpResponse
    {
        $user = ClassAuth::getCourentUser();
        $post = new Post();

        $name = new FolderName($post->name);
        $parent = null;

        if ($post->parent_id) {
            $id = new UUID($post->parent_id);
            if (!FolderClass::exists(id: $id)) {
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

        if (FolderClass::exists(name: $name, parent: $parent, user: $user)) {
            return HttpResponse::conflict(
                errors: [
                    "code" => "folder_exists",
                    "fields" => [
                        "name" => $name,
                        "parent_id" => $parent ? (string) $parent->id : null,
                        "user_id" => (string) $user->id
                    ],
                ],
                message: "Folder already exists"
            );
        }

        $folder = FolderClass::new(
            user: $user,
            name: $name,
            parent: $parent
        );

        return HttpResponse::created(
            objName: "Folder",
            data: $folder->toArray(),
        );
    }

    #[Auth("user", "manager", "admin")]
    #[Method("GET")]
    #[Cache(60)]
    #[Details(["description" => "Returngs a list of folders for the current user"])]
    public function list(): HttpResponse
    {
        return HttpResponse::success(
            message: "Folders found",
            data: ModelFolder::list(user: ClassAuth::getCourentUser())
        );
    }

    #[Auth("manager", "admin")]
    #[Method("GET")]
    #[Cache(60)]
    #[Details(["description" => "Returns a list of all folders in the system"])]
    public function all(): HttpResponse
    {
        return HttpResponse::success(
            message: "All folders found",
            data: ModelFolder::list()
        );
    }
}
