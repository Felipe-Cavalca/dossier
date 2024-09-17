<?php

namespace Bifrost\Model;

use Bifrost\Core\Database;
use Bifrost\Enum\Path;
use Bifrost\Include\acessoPropriedadesPorFuncao;

/**
 * Classe de representação dos arquivos do sistema
 *
 * @package Bifrost\Model
 */
class File
{
    use acessoPropriedadesPorFuncao;

    public string $path;

    public function __construct(string $path = "/")
    {
        $this->path = "/storage" . $path;
    }

    public function getIsDir(): bool
    {
        return is_dir($this->path);
    }

    public function getIsFile(): bool
    {
        return file_exists($this->path);
    }

    public function getContent(): string
    {
        return file_get_contents($this->path);
    }

    public function getMimeType(): string
    {
        return mime_content_type($this->path);
    }

    public function getSize(): int
    {
        return filesize($this->path);
    }

    public function setContent(string $content): bool
    {
        if ($this->isDir) {
            return false;
        }

        return file_put_contents($this->path, $content ?? "");
    }

    public function createFile(string $content): bool
    {
        $file = fopen($this->path, 'w');
        fwrite($file, $content ?? "");
        fclose($file);
        return true;
    }

    public function listFiles(): array
    {
        $files = scandir($this->path);
        return array_diff($files, [".", ".."]);
    }

    public function delete(): bool
    {
        if ($this->isDir) {
            return rmdir($this->path);
        } elseif ($this->isFile) {
            return unlink($this->path);
        }

        return false;
    }

    public function createDir(): bool
    {
        return mkdir($this->path, 0777, true);
    }
}
