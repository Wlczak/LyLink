<?php 

namespace Lylink\Interfaces\Routing;

use Closure;

interface Route {
    public static function setup(): Closure;
}