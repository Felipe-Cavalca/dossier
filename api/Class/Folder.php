<?php

namespace Bifrost\Class;

use Bifrost\Model\Folder as FolderModel;
use Bifrost\Include\DatabaseProperties;

class Folder
{
    use DatabaseProperties;

    private FolderModel $folderModel;

    public function __construct(string|array|int $folderIdentifier = null, int $userId = null)
    {
        $this->folderModel = new FolderModel();

        if ($folderIdentifier !== null && $userId !== null) {
            if (is_array($folderIdentifier) || is_string($folderIdentifier)) {
                $folderIdentifier = $this->folderModel->getIdByPath($folderIdentifier, $userId);
            }

            if (is_int($folderIdentifier)) {
                $folderData = $this->folderModel->getById($folderIdentifier, $userId);
                $this->setDatabaseProperties($folderData);
            }
        }
    }
}
