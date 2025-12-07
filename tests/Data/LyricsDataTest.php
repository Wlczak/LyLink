<?php

use Lylink\Data\LyricsData;
use Lylink\Models\Lyrics;

class LyricsDataTest extends PHPUnit\Framework\TestCase
{
    public function testConstructor(): void
    {
        $name = "test";
        $is_playing = true;
        $artist = "test";
        $lyrics = [new Lyrics(), new Lyrics(), new Lyrics()];
        $duration = 0;
        $duration_ms = 0;
        $progress_ms = 0;
        $imageUrl = "test";
        $id = "test";

        $lyricsData = new LyricsData($name, $is_playing, $artist, $lyrics, $duration, $duration_ms, $progress_ms, $imageUrl, $id);

        $this->assertSame($name, $lyricsData->name);
        $this->assertSame($is_playing, $lyricsData->is_playing);
        $this->assertSame($artist, $lyricsData->artist);
        $this->assertSame($lyrics, $lyricsData->lyrics);
        $this->assertSame($duration, $lyricsData->duration);
        $this->assertSame($duration_ms, $lyricsData->duration_ms);
        $this->assertSame($progress_ms, $lyricsData->progress_ms);
        $this->assertSame($imageUrl, $lyricsData->imageUrl);
        $this->assertSame($id, $lyricsData->id);
    }

    public function testDefaultConstructorValues():void
    {
        $name = "test";
        $is_playing = true;
        $lyrics = new LyricsData($name, $is_playing);

        $this->assertSame($name, $lyrics->name);
        $this->assertSame($is_playing, $lyrics->is_playing);
        $this->assertSame("", $lyrics->artist);
        $this->assertSame([], $lyrics->lyrics);
        $this->assertSame(0, $lyrics->duration);
        $this->assertSame(0, $lyrics->duration_ms);
        $this->assertSame(0, $lyrics->progress_ms);
        $this->assertSame(null, $lyrics->imageUrl);
        $this->assertSame(null, $lyrics->id);

    }
}
