<?php

namespace Bifrost\Controller;

use Bifrost\Include\Controller;
use Bifrost\Interface\ControllerInterface;
use Bifrost\Attributes\Auth;
use Bifrost\Attributes\Cache;
use Bifrost\Attributes\Details;
use Bifrost\Attributes\Method;
use Bifrost\Class\HttpError;
use Bifrost\Class\HttpResponse;
use Bifrost\Core\Request;
use Bifrost\Core\Session;
use Bifrost\Class\User as ClassUser;
use Bifrost\Model\User as ModelUser;

class User implements ControllerInterface
{
    use Controller;

    public function index()
    {
        switch ($_SERVER["REQUEST_METHOD"]) {
            case "GET":
                return Request::run("user", "get_users");
            case "OPTIONS":
                $controller = "user";
                return HttpResponse::returnAttributes("infos", [
                    "list_all" => Request::getOptionsAttributes($controller, "get_users")
                ]);
            default:
                return HttpError::methodNotAllowed("Method not allowed");
        }
    }

    #[Method("GET")]
    #[Cache("get_usuario", 60, ["userId"])]
    #[Auth("user", "manager", "admin")]
    #[Details([
        "description" => "Lista usuarios do sistema"
    ])]
    public function get_users()
    {
        $model = new ModelUser();
        return HttpResponse::success("Users in system", $model->getAll());
    }
}
