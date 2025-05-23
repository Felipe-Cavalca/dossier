<?php

class WebDAV
{
    private $headers;
    private $urlApi = "http://api";

    public function __construct()
    {
        $this->headers = apache_request_headers();
        $this->authenticate();
    }

    public function __toString(): string
    {
        $path = $_SERVER["REQUEST_URI"];
        $method = $_SERVER["REQUEST_METHOD"];

        switch ($method) {
            case "OPTIONS":
                $this->setHeader([
                    "HTTP/1.1 200 OK",
                    "DAV: 1,2",
                    "Allow: OPTIONS, GET, HEAD, POST, DELETE, PROPFIND, PUT, LOCK, UNLOCK, MKCOL, MOVE, PROPPATCH",
                    "Content-Length: 0"
                ]);
                break;
            case "PROPFIND":
                $response = $this->sendRequest(
                    endpoint: "/webdav/propfind?path=" . $path,
                    method: "PROPFIND"
                );
                $xml = $response->data->xml;
                $this->setHeader([
                    "HTTP/1.1 207 Multi-Status",
                    "Content-Type: application/xml; charset=utf-8",
                    "DAV: 1,2"
                ]);
                return $xml;
            default:
                $this->setHeader("HTTP/1.1 405 Method Not Allowed");
        }
        return "";
    }

    private function setHeader(string|array $header): void
    {
        if (is_array($header)) {
            foreach ($header as $h) {
                header($h);
            }
            return;
        } else {
            header($header);
        }
    }

    /**
     * Responsável por fazer a requisição para a API
     * @param string $endpoint
     * @param string $method
     * @param array $data
     * @return mixed json decoded response
     */
    private function sendRequest(string $endpoint, string $method, array $data = []): mixed
    {
        $url = $this->urlApi . $endpoint;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($data)),
            "Authorization: " . $this->headers["Authorization"],
            "Depth: " . $this->headers["Depth"]
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            curl_close($ch);
            return false;
        }

        curl_close($ch);

        return json_decode($response);
    }

    /**
     * Autentica o usuário via Basic Auth
     * @return void
     */
    public function authenticate(): void
    {
        $this->setHeader("WWW-Authenticate: Basic realm='WebDAV'");

        if (!isset($this->headers["Authorization"])) {
            $this->setHeader("HTTP/1.1 401 Unauthorized");
            exit;
        }

        list($username, $password) = explode(':', base64_decode(substr($this->headers['Authorization'], 6)));

        $result = $this->sendRequest(
            endpoint: "/auth/login",
            method: "POST",
            data: [
                "email" => $username,
                "password" => $password
            ]
        );

        if ($result === FALSE || $result->isSuccess !== true) {
            $this->setHeader("HTTP/1.1 403 Forbidden");
            exit;
        }
    }
}

echo new WebDAV();
