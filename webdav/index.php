<?php

ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '100M');

// Configuração básica
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];

// Função para autenticar via API
function authenticate()
{
    $headers = apache_request_headers();

    if (!isset($headers['Authorization'])) {
        header('HTTP/1.1 401 Unauthorized');
        header('WWW-Authenticate: Basic realm="WebDAV"');
        exit;
    }

    list($username, $password) = explode(':', base64_decode(substr($headers['Authorization'], 6)));

    // Chama a API de autenticação
    $apiUrl = "https://suaapi.com/authenticate";
    $data = http_build_query([
        'username' => $username,
        'password' => $password
    ]);

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => $data,
        ],
    ];

    $context  = stream_context_create($options);
    $result = file_get_contents($apiUrl, false, $context);

    if ($result === FALSE || json_decode($result)->success !== true) {
        header('HTTP/1.1 403 Forbidden');
        exit;
    }
}

// Função para lidar com os comandos WebDAV
function handleWebDAVRequest($method, $uri)
{
    $filePath = __DIR__ . '/data' . $uri;

    switch ($method) {
        case 'OPTIONS':
            header('DAV: 1,2');
            header('Allow: OPTIONS, GET, HEAD, POST, DELETE, PROPFIND, PUT, LOCK, UNLOCK, MKCOL, MOVE, PROPPATCH');
            break;

        case 'GET':
            if (is_dir($filePath)) {
                header("HTTP/1.1 200 OK");
                header("Content-Type: text/plain");
                $files = scandir($filePath);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        echo $file . "\n";
                    }
                }
            } elseif (file_exists($filePath)) {
                readfile($filePath);
            } else {
                header("HTTP/1.1 404 Not Found");
            }
            break;

        case 'HEAD':
            if (is_dir($filePath)) {
                header("HTTP/1.1 200 OK");
                header("Content-Type: text/plain");
            } elseif (file_exists($filePath)) {
                header("HTTP/1.1 200 OK");
                header("Content-Type: " . mime_content_type($filePath));
                header("Content-Length: " . filesize($filePath));
            } else {
                header("HTTP/1.1 404 Not Found");
            }
            break;

        case 'POST':
            file_put_contents($filePath, file_get_contents('php://input') ?? "");
            header("HTTP/1.1 200 OK");
            break;

        case 'PUT':
            file_put_contents($filePath, file_get_contents('php://input') ?? "");
            header("HTTP/1.1 201 Created");
            break;

        case 'DELETE':
            if (file_exists($filePath)) {
                unlink($filePath);
                header("HTTP/1.1 204 No Content");
            } else {
                header("HTTP/1.1 404 Not Found");
            }
            break;

        case 'PROPFIND':
            handlePropFind($filePath, $uri);
            break;

        case 'PROPPATCH':
            handlePropPatch($filePath);
            break;

        case 'LOCK':
            handleLock($filePath);
            break;

        case 'UNLOCK':
            handleUnlock($filePath);
            break;

        case 'MKCOL':
            handleMkcol($filePath);
            break;

        case 'MOVE':
            handleMove($filePath);
            break;

        default:
            header("HTTP/1.1 405 Method Not Allowed");
            break;
    }
}

// Função para lidar com o método PROPFIND
function handlePropFind($filePath, $uri)
{
    if (!file_exists($filePath)) {
        header("HTTP/1.1 404 Not Found");
        return;
    }

    $depth = isset($_SERVER['HTTP_DEPTH']) ? $_SERVER['HTTP_DEPTH'] : 'infinity';

    $xml = new DOMDocument('1.0', 'utf-8');
    $multistatus = $xml->createElement('d:multistatus');
    $multistatus->setAttribute('xmlns:d', 'DAV:');
    $xml->appendChild($multistatus);

    if (is_dir($filePath)) {
        appendPropFindResponse($xml, $multistatus, $uri, $filePath);
        if ($depth !== '0') {
            $files = scandir($filePath);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    $fullPath = $filePath . '/' . $file;
                    $fullUri = $uri . '/' . $file;
                    appendPropFindResponse($xml, $multistatus, $fullUri, $fullPath);
                }
            }
        }
    } else {
        appendPropFindResponse($xml, $multistatus, $uri, $filePath);
    }

    header('Content-Type: application/xml; charset="utf-8"');
    echo $xml->saveXML();
}

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
    $prop->appendChild($xml->createElement('d:getcontentlength', is_file($filePath) ? filesize($filePath) : ''));
    $prop->appendChild($xml->createElement('d:getlastmodified', date(DATE_RFC1123, filemtime($filePath))));
    $prop->appendChild($xml->createElement('d:getetag', md5_file($filePath)));

    $propstat->appendChild($xml->createElement('d:status', 'HTTP/1.1 200 OK'));
}

// Função para lidar com o método PROPPATCH
function handlePropPatch($filePath)
{
    if (!file_exists($filePath)) {
        header('HTTP/1.1 404 Not Found');
        return;
    }

    $propsFile = $filePath . '.props';
    $props = file_exists($propsFile) ? json_decode(file_get_contents($propsFile), true) : [];

    $xml = new DOMDocument();
    $xml->loadXML(file_get_contents('php://input'));

    foreach ($xml->getElementsByTagName('set') as $set) {
        foreach ($set->getElementsByTagName('prop') as $prop) {
            foreach ($prop->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE) {
                    $props[$child->localName] = $child->nodeValue;
                }
            }
        }
    }

    foreach ($xml->getElementsByTagName('remove') as $remove) {
        foreach ($remove->getElementsByTagName('prop') as $prop) {
            foreach ($prop->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE) {
                    unset($props[$child->localName]);
                }
            }
        }
    }

    file_put_contents($propsFile, json_encode($props));
    header('HTTP/1.1 207 Multi-Status');
}

// Função para lidar com o método LOCK
function handleLock($filePath)
{
    if (!file_exists($filePath)) {
        header('HTTP/1.1 404 Not Found');
        return;
    }

    $lockToken = 'urn:uuid:' . uuid_create(UUID_TYPE_RANDOM);
    $lockInfo = [
        'lockToken' => $lockToken,
        'owner' => $_SERVER['HTTP_HOST'],
        'timeout' => 3600,
        'depth' => 'infinity',
        'scope' => 'exclusive',
        'type' => 'write'
    ];

    // Armazenar informações de bloqueio (exemplo simples usando um arquivo)
    file_put_contents($filePath . '.lock', json_encode($lockInfo));

    header('Lock-Token: <' . $lockToken . '>');
    header('Content-Type: application/xml; charset="utf-8"');
    echo '<?xml version="1.0" encoding="utf-8"?>';
    echo '<d:prop xmlns:d="DAV:">';
    echo '<d:lockdiscovery>';
    echo '<d:activelock>';
    echo '<d:locktype><d:write/></d:locktype>';
    echo '<d:lockscope><d:exclusive/></d:lockscope>';
    echo '<d:depth>infinity</d:depth>';
    echo '<d:owner>';
    echo '<d:href>' . htmlspecialchars($_SERVER['HTTP_HOST']) . '</d:href>';
    echo '</d:owner>';
    echo '<d:timeout>Second-3600</d:timeout>';
    echo '<d:locktoken>';
    echo '<d:href>' . $lockToken . '</d:href>';
    echo '</d:locktoken>';
    echo '</d:activelock>';
    echo '</d:lockdiscovery>';
    echo '</d:prop>';
}

// Função para lidar com o método UNLOCK
function handleUnlock($filePath)
{
    header('HTTP/1.1 204 No Content');
}

// Função para lidar com o método MKCOL
function handleMkcol($filePath)
{
    if (file_exists($filePath)) {
        header("HTTP/1.1 405 Method Not Allowed");
        return;
    }

    if (mkdir($filePath, 0777, true)) {
        header("HTTP/1.1 201 Created");
    } else {
        header("HTTP/1.1 500 Internal Server Error");
    }
}

// Função para lidar com o método MOVE
function handleMove($filePath)
{
    $destination = $_SERVER['HTTP_DESTINATION'];
    $destinationPath = __DIR__ . '/data' . parse_url($destination, PHP_URL_PATH);

    if (!file_exists($filePath)) {
        header('HTTP/1.1 404 Not Found');
        return;
    }

    if (rename($filePath, $destinationPath)) {
        header('HTTP/1.1 201 Created');
    } else {
        header('HTTP/1.1 500 Internal Server Error');
    }
}

// Autenticação via API
// authenticate();

// Processa a requisição WebDAV
handleWebDAVRequest($requestMethod, $requestUri);
