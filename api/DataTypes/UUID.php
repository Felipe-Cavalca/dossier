<?php

namespace Bifrost\DataTypes;

use Bifrost\Enum\Field;
use Bifrost\Include\AbstractFieldValue;

class UUID
{
    use AbstractFieldValue;

    public function __construct(mixed $uuid)
    {
        $this->init($uuid, Field::UUID);
    }
}
