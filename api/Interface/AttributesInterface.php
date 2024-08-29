<?php

namespace Bifrost\Interface;

/**
 * Interface AttributesInterface
 *
 * Interface para os atributos
 *
 * @package Bifrost\Interface
 * @author Felipe dos S. Cavalca
 */
interface AttributesInterface
{
    // public function __construct(...$params);

    public function __destruct();

    public function beforeRun(): mixed;

    public function afterRun($return): void;
}
