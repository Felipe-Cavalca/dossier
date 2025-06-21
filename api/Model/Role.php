<?php

namespace Bifrost\Model;

use Bifrost\Core\Database;
use Bifrost\DataTypes\UUID;
use Bifrost\Core\Cache;
use Bifrost\Core\Settings;

/**
 * Modelo de roles do sistema.
 * Utiliza o cache do core (Redis) para melhorar a performance.
 */
class Role
{
    private static string $table = "roles";

    /**
     * Executa a consulta no banco e retorna o primeiro resultado.
     *
     * @param array $conditions Condições da busca
     * @return array dados da role ou vazio
     */
    private static function fetch(array $conditions): array
    {
        $database = new Database();
        $result = $database->select(
            table: self::$table . " r",
            fields: [
                "r.*",
            ],
            where: $conditions,
            limit: "1",
        );

        return $result[0] ?? [];
    }

    /**
     * Busca roles de acordo com as condições informadas.
     *
     * @param array $conditions Condições da busca
     * @return array lista de roles
     */
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

    /**
     * Retorna a role pelo ID utilizando cache.
     *
     * @param UUID $id Identificador da role
     * @return array dados da role
     */
    public static function getById(UUID $id): array
    {
        $key = Cache::buildCacheKey(
            entity: 'role',
            conditions: ['id' => (string) $id]
        );

        $settings = new Settings();
        $cache = new Cache();

        $role = $cache->get(
            key: $key,
            value: fn() => self::fetch(['r.id' => (string) $id]),
            expire: $settings->CACHE_QUERY_TIME
        );

        return $role;
    }

    /**
     * Retorna a role pelo código utilizando cache.
     *
     * @param string $code Código da role
     * @return array dados da role
     */
    public static function getByCode(string $code): array
    {
        $key = Cache::buildCacheKey(
            entity: 'role',
            conditions: ['code' => $code]
        );

        $settings = new Settings();
        $cache = new Cache();

        $role = $cache->get(
            key: $key,
            value: fn() => self::fetch(['r.code' => $code]),
            expire: $settings->CACHE_QUERY_TIME
        );

        return $role;
    }
}
