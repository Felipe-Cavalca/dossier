<?php

namespace Bifrost\Controller;

use DOMDocument;
use SimpleXMLElement;
use Bifrost\Interface\ControllerInterface;
use Bifrost\Include\Controller;
use Bifrost\Attributes\Method;
use Bifrost\Attributes\RequiredFields;
use Bifrost\Attributes\RequiredParams;
use Bifrost\Attributes\Cache;
use Bifrost\Core\Database;
use Bifrost\Class\HttpError;
use Bifrost\Class\HttpResponse;
use Bifrost\Model\File;

class Webdav implements ControllerInterface
{
    private HttpResponse $response;

    public function __construct()
    {
        $this->response = new HttpResponse();
    }

    public function auth()
    {
        $usuario = $_POST["username"];
        $password = $_POST["password"];

        if ($usuario == "admin" && $password == "admin") {
            return $this->response->buildResponse(
                message: "Login feito com sucesso",
            );
        }

        throw new HttpError("e401", ["Usuário ou senha inválidos"]);
    }

    public function GET()
    {
        $file = new File($_POST["path"]);

        if ($file->isDir) {
            return $this->response->buildResponseWebDav(
                headers: [
                    "HTTP/1.1 200 OK",
                    "Content-Type: text/plain"
                ],
                return: implode("\n", $file->listFiles())
            );
        } elseif ($file->isFile) {
            return $this->response->buildResponseWebDav(
                return: $file->content
            );
        }

        return $this->response->buildResponseWebDav(
            status: false,
            statusCode: 404,
            message: "Arquivo não encontrado",
            headers: [
                "HTTP/1.1 404 Not Found",
            ]
        );
    }

    public function HEAD()
    {
        $file = new File($_POST["path"]);

        if ($file->isDir) {
            return $this->response->buildResponseWebDav(
                headers: [
                    "HTTP/1.1 200 OK",
                    "Content-Type: text/plain"
                ],
            );
        } elseif ($file->isFile) {
            return $this->response->buildResponseWebDav(
                headers: [
                    "HTTP/1.1 200 OK",
                    "Content-Type: " . $file->mimeType,
                    "Content-Length: " . $file->size
                ],
            );
        }

        return $this->response->buildResponseWebDav(
            status: false,
            statusCode: 404,
            message: "Arquivo não encontrado",
            headers: [
                "HTTP/1.1 404 Not Found",
            ]
        );
    }

    public function POST()
    {
        $file = new File($_POST["path"]);

        if (!$file->isFile && !$file->isDir) {
            $file->createFile($_POST["content"]);
            return $this->response->buildResponseWebDav(
                headers: [
                    "HTTP/1.1 201 Created",
                ]
            );
        }

        if ($file->setContent($_POST["content"])) {
            return $this->response->buildResponseWebDav(
                headers: [
                    "HTTP/1.1 200 OK",
                ]
            );
        }

        return $this->response->buildResponseWebDav(
            headers: [
                "HTTP/1.1 500 Internal Server Error",
            ],
            data: [
                "content" => $_POST["content"]
            ]
        );
    }

    public function DELETE()
    {
        $file = new File($_POST["path"]);

        if ($file->delete()) {
            return $this->response->buildResponseWebDav(
                headers: [
                    "HTTP/1.1 204 No Content",
                ]
            );
        }

        return $this->response->buildResponseWebDav(
            headers: [
                "HTTP/1.1 404 Not Found",
            ]
        );
    }

    /**
     * @todo Implementar o método PROPFIND da maneira correta, listando os dados dos arquivos do model File. está funcionando, mas não é o ideal
     */
    public function PROPFIND()
    {
        function appendPropFindResponse($xml, $multistatus, $uri, $filePath)
        {
            $response = $xml->createElement('d:response');
            $multistatus->appendChild($response);

            $href = $xml->createElement('d:href', $uri);
            $response->appendChild($href);

            $propstat = $xml->createElement('d:propstat');
            $response->appendChild($propstat);

            $prop = $xml->createElement('d:prop');
            $propstat->appendChild($prop);

            $resourcetype = $xml->createElement('d:resourcetype');
            if (is_dir($filePath)) {
                $collection = $xml->createElement('d:collection');
                $resourcetype->appendChild($collection);
            }
            $prop->appendChild($resourcetype);

            $prop->appendChild($xml->createElement('d:displayname', basename($filePath)));
            $prop->appendChild($xml->createElement('d:getcontentlength', is_file($filePath) ? filesize($filePath) : '0'));
            $prop->appendChild($xml->createElement('d:getlastmodified', file_exists($filePath) ? date(DATE_RFC1123, filemtime($filePath)) : ''));
            $prop->appendChild($xml->createElement('d:getetag', is_file($filePath) ? md5_file($filePath) : ''));

            // Adiciona informações de espaço em disco
            if (is_dir($filePath)) {
                $freeSpace = disk_free_space($filePath);
                $totalSpace = disk_total_space($filePath);
                $prop->appendChild($xml->createElement('d:quota-available-bytes', $freeSpace));
                $prop->appendChild($xml->createElement('d:quota-used-bytes', $totalSpace - $freeSpace));
            }

            $statusCode = file_exists($filePath) ? '200 OK' : '404 Not Found';
            $propstat->appendChild($xml->createElement('d:status', 'HTTP/1.1 ' . $statusCode));
        }

        $file = new File($_POST["path"]);
        $depth = $_POST["depth"] ?? "1";

        if (!$file->isFile && !$file->isDir) {
            return $this->response->buildResponseWebDav(
                headers: [
                    "HTTP/1.1 404 Not Found",
                ]
            );
        }

        $xml = new DOMDocument('1.0', 'utf-8');
        $xml->formatOutput = true; // Facilita a leitura do XML
        $multistatus = $xml->createElement('d:multistatus');
        $multistatus->setAttribute('xmlns:d', 'DAV:');
        $xml->appendChild($multistatus);

        // Sempre adiciona a resposta para o recurso solicitado, mesmo se não existir
        appendPropFindResponse($xml, $multistatus, $_POST["path"], $file->path);

        // Se for um diretório e a profundidade for diferente de 0, adiciona os filhos
        if ($file->isDir && $depth !== '0') {
            $files = scandir($file->path);
            foreach ($files as $fileList) {
                if ($fileList !== '.' && $fileList !== '..') {
                    $fullPath = $file->path . '/' . $fileList;
                    $fullUri = $_POST["path"] . '/' . $fileList;
                    appendPropFindResponse($xml, $multistatus, $fullUri, $fullPath);
                }
            }
        }

        return $this->response->buildResponseWebDav(
            headers: [
                "HTTP/1.1 207 Multi-Status",
                "Content-Type: application/xml; charset=\"utf-8\""
            ],
            return: $xml->saveXML()
        );
    }

    public function MKCOL()
    {
        $file = new File($_POST["path"]);

        if ($file->createDir()) {
            return $this->response->buildResponseWebDav(
                headers: [
                    "HTTP/1.1 201 Created",
                ]
            );
        }

        return $this->response->buildResponseWebDav(
            headers: [
                "HTTP/1.1 500 Internal Server Error",
            ]
        );
    }

    public function MOVE()
    {
        $origem = new File($_POST["path"]);
        $destino = new File($_POST["destination"]);

        if (rename($origem->path, $destino->path)) {
            return $this->response->buildResponseWebDav(
                headers: [
                    "HTTP/1.1 201 Created",
                ]
            );
        }

        return $this->response->buildResponseWebDav(
            headers: [
                "HTTP/1.1 500 Internal Server Error",
            ]
        );
    }

    public function LOCK()
    {
        $lockToken = "opaquelocktoken:" . uniqid(); // Gera um token de bloqueio único

        $body = <<<XML
        <?xml version="1.0" encoding="utf-8"?>
        <D:prop xmlns:D="DAV:">
            <D:lockdiscovery>
                <D:activelock>
                    <D:locktype><D:write/></D:locktype>
                    <D:lockscope><D:exclusive/></D:lockscope>
                    <D:depth>infinity</D:depth>
                    <D:owner>
                        <D:href>http://example.com/</D:href>
                    </D:owner>
                    <D:timeout>Second-3600</D:timeout>
                    <D:locktoken>
                        <D:href>$lockToken</D:href>
                    </D:locktoken>
                </D:activelock>
            </D:lockdiscovery>
        </D:prop>
        XML;

        return $this->response->buildResponseWebDav(
            headers: [
                "HTTP/1.1 200 OK",
                "Content-Type: application/xml; charset=\"utf-8\"",
                "Lock-Token: <$lockToken>"
            ],
            return: $body
        );
    }

    public function UNLOCK()
    {
        return $this->response->buildResponseWebDav(
            headers: [
                "HTTP/1.1 204 No Content",
            ]
        );
    }

    public function PROPPATCH()
    {
        // Retorna a resposta XML
        return $this->response->buildResponseWebDav(
            headers: [
                "HTTP/1.1 207 Multi-Status",
                "Content-Type: application/xml; charset=\"utf-8\""
            ]
        );
    }
}
