<?php

namespace Bifrost\DataTypes;

use Bifrost\Enum\Field;
use Bifrost\Include\AbstractFieldValue;

/**
 * Armazena e valida a
 */
class Password implements \JsonSerializable
{
    use AbstractFieldValue;

    public function __construct(mixed $password)
    {
        $this->init($password, Field::PASSWORD);
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return password_hash($this->value, PASSWORD_DEFAULT);
    }
}
