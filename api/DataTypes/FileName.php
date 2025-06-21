<?php

namespace Bifrost\DataTypes;

use Bifrost\Enum\Field;
use Bifrost\Include\AbstractFieldValue;

class FileName implements \JsonSerializable
{
    use AbstractFieldValue;

    public function __construct(mixed $fileName)
    {
        $this->init($fileName, Field::FILE_NAME);
    }
}
