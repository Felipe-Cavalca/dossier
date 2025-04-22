<?php

namespace Bifrost\Class;

use Bifrost\DataTypes\UUID;
use Bifrost\Model\Folder as FolderModel;
use Bifrost\Class\User;
use Bifrost\DataTypes\FileName;
use Bifrost\DataTypes\FolderName;
use DateTime;

/**
 * @property-read UUID id
 * @property User user
 * @property Folder parent
 * @property FileName name
 * @property DateTime changed
 */
class Folder
{
    public readonly UUID $id;
    private array $data;
    private ?User $userClass = null;
    private ?Self $parentClass = null;

    public function __construct(
        UUID $id,
        array $allData = [],
    ) {
        $this->id = $id;

        if (!empty($allData)) {
            $this->data = $allData;
        }
    }

    /**
     * Retorna um json com os dados da pasta
     * @return string json com os dados
     */
    public function __toString(): string
    {
        $parent = $this->__get("parent");
        return json_encode([
            "id" => (string) $this->id,
            "name" => (string) $this->name,
            "changed" => $this->changed,
            "user" => json_decode((string) $this->__get("user")),
            "parent" => $parent ? json_decode((string) $parent) : null,
        ]);
    }

    /**
     * Retorna os dados da pasta
     * @param string $property nome da propriedade
     * @return mixed valor da propriedade
     */
    public function __get(string $property): mixed
    {
        switch ($property) {
            case "user":
                return $this->getUser();
                break;
            case "parent":
                return $this->getParent();
                break;
            case "user_id":
            case "parent_id":
            case "name":
            case "changed":
                if (empty($this->data)) {
                    $this->data = FolderModel::getById($this->id);
                }
                return $this->data[$property];
            default:
                return null;
        }
    }

    /**
     * Retorna a pasta Pai da pasta atual
     * @return Self Pasta Pai
     */
    private function getParent(): ?Self
    {
        if ($this->parentClass === null && $this->__get("parent_id") !== null) {
            $this->parentClass = new Folder(id: $this->__get("parent_id"));
        }

        return $this->parentClass;
    }

    /**
     * Retorna o dono
     * @return User Dono da pasta
     */
    private function getUser(): User
    {
        if ($this->userClass === null) {
            $this->userClass = new User(id: $this->__get("user_id"));
        }

        return $this->userClass;
    }

    /**
     * Valida se uma pasta já existe
     * @param FolderName $name nome da pasta
     * @param self|User $reference Pasta pai ou o dono da pasta caso ela esteja na raiz
     * @return bool a pasta existe ou não
     */
    public static function exists(FolderName $name, self|User $reference): bool
    {
        return FolderModel::exists(name: $name, reference: $reference);
    }

    /**
     * Cria uma nova pasta no sistema
     * @param User $user dono da pasta
     * @param FolderName $name nome da pasta
     * @param self $parent pasta pai
     * @return self Pasta Criada no sistema
     */
    public static function new(User $user, FolderName $name, ?self $parent = null): self
    {
        $reference = $parent === null ? $user : $parent;

        if (self::exists(name: $name, reference: $reference)) {
            throw new EntityDuplicateException("Folder");
        }

        $data = FolderModel::new(
            user: $user,
            name: $name,
            parent: $parent
        );

        return new self(
            id: $data["id"],
            allData: $data
        );
    }
}
