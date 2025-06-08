<?php

namespace Bifrost\Class;

use Bifrost\DataTypes\UUID;
use Bifrost\Model\Role as RoleModel;
use Bifrost\Class\HttpResponse;
use Bifrost\Core\AppError;
use Bifrost\Core\Settings;

/**
 * Representa uma role do sistema.
 *
 * @property UUID $id
 * @property string $code
 * @property string $name
 * @property string $description
 */
class Role implements \JsonSerializable
{
    public UUID $id;
    public string $code;
    public string $name;
    public string $description;

    private const DEFAULT_ROLE = 'user';

    /**
     * Constrói a instância carregando os dados da role.
     * Se nenhum parâmetro for fornecido, usa o código padrão.
     *
     * @param UUID|null $id   Identificador da role
     * @param string|null $code Código da role
     */
    public function __construct(
        ?UUID $id = null,
        ?string $code = null,
    ) {
        $settings = new Settings();
        $defaultCode = $settings->DEFAULT_ROLE_CODE ?? self::DEFAULT_ROLE;

        if ($id !== null) {
            $roleData = RoleModel::getById($id);
        } elseif ($code !== null) {
            $roleData = RoleModel::getByCode($code);
        } elseif (empty($id) && empty($code)) {
            $roleData = RoleModel::getByCode($defaultCode);
        }

        if (empty($roleData)) {
            throw new AppError(
                HttpResponse::internalServerError(
                    errors: [
                        "id" => $id,
                        "code" => $code,
                    ],
                    message: "Role not found"
                )
            );
        }

        $this->id = new UUID($roleData["id"]);
        $this->code = $roleData["code"];
        $this->name = $roleData["name"];
        $this->description = $roleData["description"];
    }

    /**
     * Retorna a representação JSON da role.
     *
     * @return string JSON da role
     */
    public function __toString(): string
    {
        return json_encode($this->jsonSerialize());
    }

    /**
     * Prepara os dados para serialização em JSON.
     *
     * @return array dados da role
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => (string) $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}
