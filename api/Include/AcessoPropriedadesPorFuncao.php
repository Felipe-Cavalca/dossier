<?php

namespace Bifrost\Include;

/**
 * Acesso Propriedades Por Função
 *
 * Permite que as propriedades de uma classe sejam acessadas por funções
 *
 * @package Bifrost\Include
 * @author Felipe dos S. Cavalca
 */
trait acessoPropriedadesPorFuncao
{
    public function __get($propriedade): mixed
    {
        $propriedade = ucfirst($propriedade);
        $nomeFuncao = "get{$propriedade}";

        if (method_exists($this, $nomeFuncao)) {
            return $this->$nomeFuncao();
        }

        return null;
    }
}
