<?php

namespace Lylink\Interfaces\Datatypes;

abstract class Track
{
    public Album $album;
    /**
     * @var Artist[]
     */
    public array $artists;
    /**
     * @var string[]
     */
    public array $available_markets;
    public int $disc_number;
    public int $duration_ms;
    public bool $explicit;
    public ExternalUrls|null $external_urls;
    public string $href;
    public string $id;
    public bool $is_playable;
    public string $name;
    public int $popularity;
    public string|null $preview_url;
    public int $track_number;
    public string $type;
    public string $uri;
    public bool $is_local;
}
