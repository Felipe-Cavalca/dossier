<?php

namespace Bifrost\Controller;

use Bifrost\Interface\ControllerInterface;
use Bifrost\Include\Controller;
use Bifrost\Attributes\Method;
use Bifrost\Attributes\RequiredFields;
use Bifrost\Attributes\RequiredParams;
use Bifrost\Attributes\Cache;
use Bifrost\Core\Settings;

class Index implements ControllerInterface
{
    use Controller;

    #[Method(["GET"])]
    #[Cache("index-getVars", 10)]
    public function getVars()
    {
        $settings = new Settings();
        return $settings->app;
    }

    #[Method(["GET"])]
    #[Cache("index-data", 10)]
    public function index()
    {
        return "Index";
    }

    #[Method(["POST"])]
    #[RequiredFields([
        "email" => FILTER_VALIDATE_EMAIL,
        "numero" => FILTER_VALIDATE_INT,
    ])]
    #[RequiredParams(["id"])]
    #[Cache("index-data", 10)]
    public function data()
    {
        return [
            "id" => $_GET["id"],
            "email" => $_POST["email"],
            "numero" => $_POST["numero"],
        ];
    }
}
