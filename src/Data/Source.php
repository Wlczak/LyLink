<?php

namespace Lylink\Data;

class Source
{
    public int $id;
    public string $name;
    public string $route;
    public ?CurrentSong $current_song;

    public function __construct(
        int $id,
        string $name,
        string $route,
        ?CurrentSong $current_song = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->route = $route;
        $this->current_song = $current_song;
    }

    public function hasSong(): bool
    {
        return $this->current_song !== null;
    }
}
