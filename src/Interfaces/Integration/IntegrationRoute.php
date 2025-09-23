<?php

namespace Lylink\Interfaces\Integration;

use Closure;

interface IntegrationRoute
{
    public static function setup(): Closure;

    public static function connect(): string;
    public static function connectPost(): string;
    public static function disconnect(): string;
}
