<?php

namespace Bifrost\Controller;

use Bifrost\Attributes\Auth;
use Bifrost\Attributes\Cache;
use Bifrost\Interface\ControllerInterface;
use Bifrost\Attributes\Method;
use Bifrost\Attributes\Details;
use Bifrost\Attributes\OptionalParams;
use Bifrost\Class\Auth as ClassAuth;
use Bifrost\Class\Folder;
use Bifrost\Core\Database;
use Bifrost\Class\HttpResponse;
use Bifrost\Core\Get;
use Bifrost\DataTypes\FilePath;
use Bifrost\DataTypes\FolderName;
use Bifrost\DataTypes\UUID;
use Bifrost\Enum\Field;
use Bifrost\Enum\HttpStatusCode;

class Webdav implements ControllerInterface
{

    public function index() {}

    #[Auth("user", "manager", "admin")]
    #[Details(["description" => "Retorna o conteúdo do arquivo ou diretório"])]
    #[Method("PROPFIND")]
    #[OptionalParams([
        "path" => Field::STRING
    ])]
    public function PROPFIND()
    {
        $get = new Get();
        $path = new FilePath(htmlspecialchars_decode($get->path ?? "/"));
        $user = ClassAuth::getCourentUser();
        $database = new Database();
        $depth = $_SERVER['HTTP_DEPTH'] ?? '1';

        $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
        $xml .= "<D:multistatus xmlns:D=\"DAV:\">\n";

        // Se for a raiz, monta manualmente a resposta para "/"
        if ((string)$path === "/") {
            $xml .= "  <D:response>\n";
            $xml .= "    <D:href>/</D:href>\n";
            $xml .= "    <D:propstat>\n";
            $xml .= "      <D:prop>\n";
            $xml .= "        <D:displayname>/</D:displayname>\n";
            $xml .= "        <D:resourcetype><D:collection/></D:resourcetype>\n";
            $xml .= "        <D:getcontentlength>0</D:getcontentlength>\n";
            $xml .= "        <D:getlastmodified>" . gmdate("D, d M Y H:i:s") . " GMT</D:getlastmodified>\n";
            $xml .= "      </D:prop>\n";
            $xml .= "      <D:status>HTTP/1.1 200 OK</D:status>\n";
            $xml .= "    </D:propstat>\n";
            $xml .= "  </D:response>\n";
        } else {
            // Busca o próprio diretório/arquivo normalmente
            $result = $database->query(
                select: "*",
                from: "file_structure",
                where: [
                    "path" => (string) $path,
                    "user_id" => (string) $user->id
                ]
            );
            foreach ($result as $row) {
                $isDir = $row["type"] === "folder";
                $href = $row["path"];
                if ($isDir && substr($href, -1) !== "/") {
                    $href .= "/";
                }
                $xml .= "  <D:response>\n";
                $xml .= "    <D:href>" . htmlspecialchars($href) . "</D:href>\n";
                $xml .= "    <D:propstat>\n";
                $xml .= "      <D:prop>\n";
                $xml .= "        <D:displayname>{$row["name"]}</D:displayname>\n";
                $xml .= "        <D:resourcetype>" . ($isDir ? "<D:collection/>" : "") . "</D:resourcetype>\n";
                $xml .= "        <D:getcontentlength>{$row["size"]}</D:getcontentlength>\n";
                $xml .= "        <D:getlastmodified>" . gmdate("D, d M Y H:i:s", strtotime($row["modified"])) . " GMT</D:getlastmodified>\n";
                $xml .= "      </D:prop>\n";
                $xml .= "      <D:status>HTTP/1.1 200 OK</D:status>\n";
                $xml .= "    </D:propstat>\n";
                $xml .= "  </D:response>\n";
            }
        }

        // Filhos (apenas se Depth > 0)
        if ($depth !== '0') {
            $children = $database->query(
                select: "*",
                from: "file_structure",
                where: [
                    "parent_path" => (string) $path,
                    "user_id" => (string) $user->id
                ]
            );
            foreach ($children as $row) {
                $isDir = $row["type"] === "folder";
                $href = $row["path"];
                if ($isDir && substr($href, -1) !== "/") {
                    $href .= "/";
                }
                $xml .= "  <D:response>\n";
                $xml .= "    <D:href>" . htmlspecialchars($href) . "</D:href>\n";
                $xml .= "    <D:propstat>\n";
                $xml .= "      <D:prop>\n";
                $xml .= "        <D:displayname>{$row["name"]}</D:displayname>\n";
                $xml .= "        <D:resourcetype>" . ($isDir ? "<D:collection/>" : "") . "</D:resourcetype>\n";
                $xml .= "        <D:getcontentlength>{$row["size"]}</D:getcontentlength>\n";
                $xml .= "        <D:getlastmodified>" . gmdate("D, d M Y H:i:s", strtotime($row["modified"])) . " GMT</D:getlastmodified>\n";
                $xml .= "      </D:prop>\n";
                $xml .= "      <D:status>HTTP/1.1 200 OK</D:status>\n";
                $xml .= "    </D:propstat>\n";
                $xml .= "  </D:response>\n";
            }
        }

        $xml .= "</D:multistatus>";

        return new HttpResponse(
            statusCode: HttpStatusCode::OK,
            message: "Listagem de pastas",
            data: ["xml" => $xml]
        );
    }

    #[Auth("user", "manager", "admin")]
    #[Details(["description" => "Cria um novo diretório"])]
    #[Method("MKCOL")]
    #[OptionalParams([
        "path" => Field::STRING
    ])]
    public function MKCOL(): HttpResponse
    {
        $database = new Database();
        $get = new Get();
        $user = ClassAuth::getCourentUser();
        $path = htmlspecialchars_decode($get->path ?? "/");
        $parentName = dirname((string) $path);
        $name = basename((string) $path);

        $parent = null;
        if ($parentName != "/") {
            $parentData = $database->query(
                select: "*",
                from: "file_structure",
                where: [
                    "path" => $parentName,
                    "user_id" => $user->id
                ]
            );

            if (empty($parentData)) {
                return new HttpResponse(
                    statusCode: HttpStatusCode::NOT_FOUND,
                    message: "Diretório pai não encontrado"
                );
            }

            $parent = new Folder(id: new UUID($parentData[0]["id"] ?? null));
        }

        Folder::new(
            user: $user,
            name: new FolderName($name),
            parent: $parent
        );

        return new HttpResponse(
            statusCode: HttpStatusCode::CREATED,
            message: "Diretório criado com sucesso"
        );
    }
}
