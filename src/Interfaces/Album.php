<?php
abstract class Album
{
    public string $album_type;
    public int $total_tracks;
    /**
     * @var string[]
     */
    public array $available_markets;
    public ExternalUrls|null $external_urls;
    public string $href;
    public string $id;
    /**
     * @var Image[]
     */
    public array $images;
    public string $name;
    public string $release_date;
    public string $release_date_precision;
    public string $type;
    public string $uri;
    /**
     * @var Artist[]
     */
    public array $artists;
}
