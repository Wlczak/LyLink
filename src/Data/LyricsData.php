<?php

namespace Lylink\Data;

use Lylink\Models\Lyrics;

class LyricsData
{
    public string $name;
    public string $artist;
    /**
     * @var array<Lyrics>
     */
    public array $lyrics;
    public int $duration;
    public int $duration_ms;
    public int $progress_ms;
    public ?string $imageUrl;
    public ?string $id;
    public bool $is_playing;

    /**
     * @param array<Lyrics> $lyrics
     */
    public function __construct(
        string $name,
        bool $is_playing,
        string $artist = "",
        array $lyrics = [],
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
