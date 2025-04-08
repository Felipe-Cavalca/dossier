<?php

namespace Bifrost\Class;

class EntityNotFoundException extends \DomainException
{
    public function __construct(string $entity, array $params = [])
    {
        parent::__construct("{$entity} not found.");
        $this->entity = $entity;
        $this->params = $params;
    }

    public string $entity;
    public array $params;
}
