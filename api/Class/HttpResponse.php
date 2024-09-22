<?php

namespace Bifrost\Class;

class HttpResponse
{
    public static function buildResponse(
        string $message,
        bool $status = true,
        int $statusCode = 200,
        array|string $data = [],
    ): array {
        $dateTime = new \DateTime();
        return [
            "status" => $status,
            "statusCode" => $statusCode,
            "message" => $message,
            "data" => is_string($data) ? json_decode($data) : $data,
            "date" => $dateTime->format('Y-m-d H:i:s.uP')
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
