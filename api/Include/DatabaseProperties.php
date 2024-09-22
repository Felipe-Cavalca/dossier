<?php

namespace Bifrost\Include;

trait DatabaseProperties
{
    private array $databaseProperties;

    public function setDatabaseProperties(array $properties): void
    {
        $this->databaseProperties = $properties;
    }

    public function __toString(): string
    {
        return json_encode($this->databaseProperties);
    }

    public function __get(string $property): mixed
    {
        return $this->databaseProperties[$property] ?? null;
    }

    public function __set(string $property, mixed $value): void
    {
        $this->databaseProperties[$property] = $value;
    }

    public function __isset(string $property): bool
    {
        return isset($this->databaseProperties[$property]);
    }
}
