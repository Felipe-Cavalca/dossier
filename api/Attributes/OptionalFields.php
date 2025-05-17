<?php

namespace Bifrost\Attributes;

use Bifrost\Include\AtrributesDefaultMethods;
use Bifrost\Interface\AttributesInterface;

class OptionalFields implements AttributesInterface
{

    use AtrributesDefaultMethods;

    /**
     * @var array - Campos opcionais a serem mostrados caso a requisição seja OPTION.
     */
    public static array $details = [];

    /**
     * @param array $params - Campos opcionais a serem mostrados.
     */
    public function __construct(...$params)
    {
        self::$details = $params[0];
    }

    public function getOptions(): array
    {
        return self::$details;
    }
}
