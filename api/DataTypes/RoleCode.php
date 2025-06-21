<?php

namespace Bifrost\DataTypes;

use Bifrost\Enum\Field;
use Bifrost\Include\AbstractFieldValue;

class RoleCode implements \JsonSerializable
{
    use AbstractFieldValue;

    public function __construct(mixed $roleCode)
    {
        $this->init($roleCode, Field::URL);
    }
}
