<?php
abstract class Artist
{
    public ExternalUrls|null $external_urls;
    public string $href;
    public string $id;
    public string $name;
    public string $type;
    public string $uri;
}
