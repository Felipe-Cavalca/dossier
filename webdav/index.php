<?php

class WebDAV
{
    public $headers;
    public $method;
    private $urlApi = "http://api/webdav";

    public function __construct()
    {
        $this->headers = apache_request_headers();
        $this->method = $_SERVER["REQUEST_METHOD"];
        $this->authenticate();
    }

    public function __toString()
    {
        $path = $_SERVER["REQUEST_URI"];
        $method = $this->method;

        switch ($method) {

            case "OPTIONS":
                header('DAV: 1,2');
                header('Allow: OPTIONS, GET, HEAD, POST, DELETE, PROPFIND, PUT, LOCK, UNLOCK, MKCOL, MOVE, PROPPATCH');
                break;
            case "GET":
                return $this->getReturnApi("/get", ["path" => $path]);
            case "HEAD":
                return $this->getReturnApi("/head", ["path" => $path]);
            case "DELETE":
                return $this->getReturnApi("/delete", ["path" => $path]);
            case "MKCOL":
                return $this->getReturnApi("/mkcol", ["path" => $path]);
            case "MOVE":
                return $this->getReturnApi("/move", [
                    "path" => $path,
                    "destination" =>  parse_url($_SERVER["HTTP_DESTINATION"], PHP_URL_PATH)
                ]);
            case "PUT":
            case "POST":
                return $this->getReturnApi("/post", [
                    "path" => $path,
                    "content" => base64_encode(file_get_contents("php://input") ?? "a")
                ]);
            case "PROPFIND":
            case "PROPPATCH":
                return $this->getReturnApi("/propfind", [
                    "path" => $path,
                    "depth" => isset($_SERVER['HTTP_DEPTH']) ? $_SERVER['HTTP_DEPTH'] : 'infinity'
                ]);
            case "LOCK":
                return $this->getReturnApi("/lock", [
                    "path" => $path,
                    "headers" => $this->headers,
                    "body" => file_get_contents("php://input") ?? ""
                ]);
            case "UNLOCK":
                return $this->getReturnApi("/unlock", [
                    "path" => $path,
                    "lock_token" => $_SERVER["HTTP_LOCK_TOKEN"] ?? ""
                ]);
            default:
                $this->setHeader("HTTP/1.1 405 Method Not Allowed");
                exit;
        }
        exit;
    }

    private function setHeader($header): void
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

    private function makeRequest(string $method, string $path, array $data = [])
    {
        $url = $this->urlApi . $path;
        $data = http_build_query($data);

        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => $method,
                'content' => $data,
            ],
        ];

        $context  = stream_context_create($options);
        return json_decode(file_get_contents($url, false, $context));
    }

    private function getReturnApi(string $endpoint, array $dados): string
    {
        $url = $this->urlApi . $endpoint;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($dados))
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            curl_close($ch);
            return false;
        }

        curl_close($ch);

        $response = json_decode($response);

        if ($response->status === true) {
            $this->setHeader($response->headers);
            return $response->return;
        }

        $this->setHeader("HTTP/1.1 404 Not Found");
        exit;
    }

    public function authenticate()
    {
        if (!isset($this->headers["Authorization"])) {
            $this->setHeader("HTTP/1.1 401 Unauthorized");
            $this->setHeader("WWW-Authenticate: Basic realm='WebDAV'");
            exit;
        }

        list($username, $password) = explode(':', base64_decode(substr($this->headers['Authorization'], 6)));

        $result = $this->makeRequest("POST", "/auth", [
            'username' => $username,
            'password' => $password
        ]);

        if ($result === FALSE || $result->status !== true) {
            $this->setHeader("HTTP/1.1 403 Forbidden");
            exit;
        }
    }
}

echo new WebDAV();
