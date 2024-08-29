<?php

namespace Bifrost\Enum;

enum Path: string
{
    case CLASSE = "Bifrost\\Class\\";
    case CONTROLLERS = "Bifrost\\Controller\\";
    case MODEL = "Bifrost\\Model\\";
}
