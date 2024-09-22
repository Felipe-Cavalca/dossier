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

    #[Method('GET')]
    #[RequiredParams(['user_id'])]
    #[Cache('folder-list', 5)]
    public function index(): array
    {
        $path = $_GET['path'] ?? null;

        if ($path) {
            $folder = new FolderClass($path, $_GET['user_id']);
        }

        if (!isset($folder->id)) {
            throw new HttpError("e404", ["path" => "A pasta não existe"]);
        }

        return HttpResponse::buildResponse(
            message: "Listagem de pastas",
            data: $folder
        );
    }
}
