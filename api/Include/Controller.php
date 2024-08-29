<?php

namespace Bifrost\Include;

/**
 * Trait Controller
 *
 * Funções base para um controller
 *
 * @package Bifrost\Shared
 * @author Felipe dos S. Cavalca
 */
trait Controller
{
    public array $get = [];
    public array $post = [];

    public function __construct()
    {
        $this->get = $_GET;
        $this->post = $_POST;
    }
}
