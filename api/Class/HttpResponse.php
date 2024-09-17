<?php

namespace Bifrost\Class;

/**
 * Classe responsável por gerenciar as response
 *
 * @package Bifrost\Class
 * @author Felipe dos S. Cavalca
 */
class HttpResponse
{
    public function buildResponse(
        string $message,
        bool $status = true,
        int $statusCode = 200,
        array $data = [],
    ): array {
        return [
            "status" => $status,
            "statusCode" => $statusCode,
            "message" => $message,
            "data" => $data
        ];
    }

    public function buildResponseWebDav(
        bool $status = true,
        int $statusCode = 200,
        string $message = null,
        array $headers = [],
        array $data = [],
        string $return = "",
    ): array {
        return [
            "status" => $status,
            "statusCode" => $statusCode,
            "message" => $message,
            "headers" => $headers,
            "data" => $data,
            "return" => $return
        ];
    }
}
