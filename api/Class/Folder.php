<?php

namespace Bifrost\Class;

use Bifrost\DataTypes\UUID;
use Bifrost\Model\Folder as FolderModel;
use Bifrost\Class\User;
use Bifrost\DataTypes\FileName;
use Bifrost\DataTypes\FolderName;
use DateTime;
use JsonSerializable;

/**
 * @property-read UUID id
 * @property User user
 * @property Folder parent
 * @property FileName name
 * @property DateTime changed
 */
class Folder implements JsonSerializable
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
        return json_encode(
            $this->toArray(),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
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
                    $this->data = FolderModel::list($this->id, first: true);
                }
                return $this->data[$property] ?? null;
            default:
                return null;
        }
    }

    /**
     * Retorna os dados da pasta em formato json
     * @return array dados da pasta
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Retorna os dados da pasta em formato array
     * @return array dados da pasta
     */
    public function toArray(): array
    {
        $parent = $this->__get("parent");
        return [
            "id" => $this->id,
            "name" => $this->name,
            "changed" => $this->changed,
            "user" => $this->__get("user"),
            "parent" => $parent,
        ];
    }

    /**
     * Retorna a pasta Pai da pasta atual
     * @return Self Pasta Pai
     */
    private function getParent(): ?Self
    {
        if ($this->parentClass === null && $this->parent_id !== null) {
            $this->parentClass = new Folder(id: $this->parent_id);
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
            $this->userClass = new User(id: $this->user_id);
        }

        return $this->userClass;
    }

    /**
     * Valida se uma pasta já existe
     * @param UUID $id ID da pasta
     * @param FolderName $name Nome da pasta
     * @param self $parent Pasta Pai
     * @param User $user Usuário dono da pasta
     * @return bool a pasta existe ou não
     */
    public static function exists(
        ?UUID $id = null,
        ?FolderName $name = null,
        ?self $parent = null,
        ?User $user = null,
    ): bool {
        return FolderModel::exists(
            id: $id,
            name: $name,
            parent: $parent,
            user: $user
        );
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
        if (self::exists(name: $name, parent: $parent, user: $user)) {
            throw new EntityDuplicateException("Folder");
        }

        return FolderModel::new(
            user: $user,
            name: $name,
            parent: $parent
        );
    }
}
