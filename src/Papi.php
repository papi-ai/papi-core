<?php

namespace PapiAi\Core;

class Papi
{
    public function __construct()
    {
    }

    public function version(): string
    {
        return '0.1.0';
    }

    public static function agent(): Agent
    {
        return new Agent();
    }
}
