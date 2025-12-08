<?php

namespace Lylink\Traits;

use Closure;
use Pecee\SimpleRouter\SimpleRouter;

trait IntegrationRoutingSetup
{
    /**
     * @overwritable
     */
    public static function setup(): Closure
    {
        return self::traitSetup();
    }

    public static function traitSetup():Closure
    {
        return function () {
            SimpleRouter::get('/connect', [self::class, 'connect']);
            SimpleRouter::post('/connect', [self::class, 'connectPost']);
            SimpleRouter::get('/disconnect', [self::class, 'disconnect']);
        };
    }
}
