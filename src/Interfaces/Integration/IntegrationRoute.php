<?php

namespace Lylink\Interfaces\Integration;

use Closure;

interface IntegrationRoute
{
    public static function connect(): string;
    public static function connectPost(): string;
    public static function disconnect(): string;
    public static function setup(): Closure;
}
