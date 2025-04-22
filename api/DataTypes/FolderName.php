<?php

namespace Bifrost\DataTypes;

use Bifrost\Enum\Field;
use Bifrost\Include\AbstractFieldValue;

/**
 * Armazena e valida o nome de uma pasta
 */
class FolderName
{
    use AbstractFieldValue;

    public function __construct(mixed $folderName)
    {
        $this->init($folderName, Field::FOLDER_NAME);
    }
}
