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
        $this->init($password, Field::STRING);
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return password_hash($this->value, PASSWORD_DEFAULT);
    }

    public function validate(string $hash): bool
    {
        return password_verify(
            password: $this->value,
            hash: $hash
        );
    }
}
