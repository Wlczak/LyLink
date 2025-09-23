<?php

namespace Lylink\Interfaces\Integration;

interface IntegrationRoute
{
    public static function connect(): string;
    public static function connectPost(): string;
    public static function disconnect(): string;
}
