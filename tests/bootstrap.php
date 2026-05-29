<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

ini_set('session.save_path', sys_get_temp_dir());

require_once __DIR__ . '/Fakes/SpotifyWebAPI.php';

if (!class_exists(\Uri\InvalidUriException::class, false)) {
    class_alias(\InvalidArgumentException::class, \Uri\InvalidUriException::class);
}

require_once __DIR__ . '/../src/Middleware/AuthMiddleware.php';
