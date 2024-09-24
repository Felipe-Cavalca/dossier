<?php

namespace Bifrost\Controller;

use Bifrost\Core\Database;
use Bifrost\Attributes\Cache;
use Bifrost\Attributes\Method;
use Bifrost\Attributes\RequiredFields;
use Bifrost\Attributes\RequiredParams;
use Bifrost\Class\HttpResponse;
use Bifrost\Class\HttpError;
use Bifrost\Class\Folder as FolderClass;
use Bifrost\Interface\ControllerInterface;

class Folder implements ControllerInterface
{

    public function __construct() {}

    #[Method("GET")]
    #[RequiredParams([
        "user_id" => FILTER_VALIDATE_INT
    ])]
    #[Cache("folder-list", 5)]
    public function details(): array
    {
        $path = (int) $_GET["id"] ?? (string) $_GET["path"] ?? null;

        if ($path) {
            $folder = new FolderClass($path, $_GET["user_id"]);
        }

        if (!isset($folder->id)) {
            throw HttpError::notFound("A pasta $path não existe");
        }

        return HttpResponse::success(
            message: "Listagem de pastas",
            data: $folder
        );
    }

    #[Method("POST")]
    #[RequiredFields([
        "user_id" => FILTER_VALIDATE_INT,
        // "parent_id" => FILTER_VALIDATE_INT,
        "name" => FILTER_SANITIZE_SPECIAL_CHARS,
    ])]
    public function create(): array
    {
        $database = new Database();
        $folder = new FolderClass();

        $database->setSystemIdentifier([
            "user_id" => $_POST["user_id"]
        ]);

        $dataInsert = [
            "user_id" => (int) $_POST["user_id"],
            "parent_id" => (int) $_POST["parent_id"] ?? null,
            "name" => (string) $_POST["name"]
        ];

        $insert = $database->insert("folder", $dataInsert);

        $folder = new FolderClass($insert, $_POST["user_id"]);

        return HttpResponse::success(
            message: "Pasta criada com sucesso",
            data: $folder
        );
    }
}
