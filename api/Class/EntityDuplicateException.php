<?php

namespace Bifrost\Class;

class EntityDuplicateException extends \DomainException
{
    public function __construct(string $entity, array $params = [])
    {
        parent::__construct("{$entity} duplicate.");
        $this->entity = $entity;
        $this->params = $params;
    }

    public string $entity;
    public array $params;
}
