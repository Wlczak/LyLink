<?php

namespace Lylink\Data;

class CurrentSong
{
    public ?string $id;
    public ?string $title;
    public ?string $artist;
    public ?string $imageUrl;
    public int $progress_ms;
    public int $duration_ms;

    public function __construct(
        ?string $id = null,
        ?string $title = null,
        ?string $artist = null,
        ?string $imageUrl = null,
        int $progress_ms = 0,
        int $duration_ms = 0
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->artist = $artist;
        $this->imageUrl = $imageUrl;
        $this->progress_ms = $progress_ms;
        $this->duration_ms = $duration_ms;
    }

    public function getProgressPercent(): float
    {
        return $this->duration_ms > 0
            ? ($this->progress_ms / $this->duration_ms) * 100
            : 0;
    }
}

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
