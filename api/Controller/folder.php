<?php

namespace Bifrost\Controller;

use Bifrost\Attributes\Cache;
use Bifrost\Interface\ControllerInterface;
use Bifrost\Attributes\Method;
use Bifrost\Attributes\RequiredParams;
use Bifrost\Class\HttpResponse;
use Bifrost\Class\HttpError;
use Bifrost\Class\Folder as FolderClass;

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
}
