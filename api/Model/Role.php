<?php

namespace Bifrost\Model;

use Bifrost\Core\Database;
use Bifrost\DataTypes\UUID;
use Bifrost\Core\Cache;

class Role
{
    private static string $table = "roles";
    private static int $expireCache = 3600;

    public static function search(array $conditions): array
    {
        $database = new Database();
        return $database->select(
            table: self::$table . " r",
            fields: [
                "r.*",
            ],
            where: $conditions
        );
    }

    public static function getById(UUID $id): array
    {
        $cache = new Cache();
        $cacheKey = "role:id:" . (string) $id;

        $cached = $cache->get(key: $cacheKey, expire: self::$expireCache);

        if ($cached === false) {
            $cached = self::search(["r.id" => (string) $id])[0] ?? [];
            $cache->set(key: $cacheKey, value: $cached, expire: self::$expireCache);
        }

        return $cached;
    }

    public static function getByCode(string $code): array
    {
        $cache = new Cache();
        $cacheKey = "role:code:" . $code;

        $cached = $cache->get(key: $cacheKey, expire: self::$expireCache);

        if ($cached === false) {
            $cached = self::search(["r.code" => $code])[0] ?? [];
            $cache->set(key: $cacheKey, value: $cached, expire: self::$expireCache);
        }

        return $cached;
    }
}
