<?php

namespace App\Enum;

enum CheckMethod: string
{
    case HTTP = 'http';
    case BROWSER = 'browser';
}
