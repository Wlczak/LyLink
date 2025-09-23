<?php

namespace Lylink\Data;

class LyricsData
{
    public string $name;
    public string $artist;
    public string $lyrics;
    public int $duration;
    public int $duration_ms;
    public int $progress_ms;
    public ?string $imageUrl;
    public ?string $id;
    public bool $is_playing;

    public function __construct(
        string $name,
        bool $is_playing,
        string $artist = "",
        string $lyrics = "",
        int $duration = 0,
        int $duration_ms = 0,
        int $progress_ms = 0,
        ?string $imageUrl = null,
        ?string $id = null,
    ) {
        $this->name = $name;
        $this->artist = $artist;
        $this->lyrics = $lyrics;
        $this->duration = $duration;
        $this->duration_ms = $duration_ms;
        $this->progress_ms = $progress_ms;
        $this->imageUrl = $imageUrl;
        $this->id = $id;
        $this->is_playing = $is_playing;
    }
}
