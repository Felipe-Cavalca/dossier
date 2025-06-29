<?php

namespace Bifrost\DataTypes;

use Bifrost\Enum\Field;
use DateTime as BaseDateTime;

class DateTime implements \JsonSerializable
{
    private BaseDateTime $dateTime;

    public function __construct(string $dateTimeString)
    {
        $this->dateTime = new BaseDateTime($dateTimeString);
    }

    public function __toString(): string
    {
        return $this->dateTime->format('Y-m-d H:i:s.uP');
    }

    public function jsonSerialize(): string
    {
        return $this->__toString();
    }
}
