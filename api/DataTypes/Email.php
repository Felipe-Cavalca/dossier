<?php

namespace Bifrost\DataTypes;

use Bifrost\Enum\Field;
use Bifrost\Include\AbstractFieldValue;

class Email implements \JsonSerializable
{
    use AbstractFieldValue;

    public function __construct(mixed $email)
    {
        $this->init($email, Field::EMAIL);
    }
}
