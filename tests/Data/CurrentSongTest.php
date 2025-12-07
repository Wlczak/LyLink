<?php

use Lylink\Data\CurrentSong;
use PHPUnit\Framework\TestCase;

final class CurrentSongTest extends TestCase
{
    public function testDefaultConstructorValues()
    {
        $currentSong = new CurrentSong();
        $this->assertNull($currentSong->id);
        $this->assertNull($currentSong->title);
        $this->assertNull($currentSong->artist);
        $this->assertNull($currentSong->imageUrl);
        $this->assertEquals(0, $currentSong->progress_ms);
        $this->assertEquals(0, $currentSong->duration_ms);
    }

    public function testSetConstructionValues()
    {
        $currentSong = new CurrentSong(
            "id",
            "title",
            "artist",
            "imageUrl",
            1,
            2
        );
        $this->assertEquals("id", $currentSong->id);
        $this->assertEquals("title", $currentSong->title);
        $this->assertEquals("artist", $currentSong->artist);
        $this->assertEquals("imageUrl", $currentSong->imageUrl);
        $this->assertEquals(1, $currentSong->progress_ms);
        $this->assertEquals(2, $currentSong->duration_ms);
    }

    public function testGetProgressPercent()
    {
        for ($baseTime = 0; $baseTime < 100; $baseTime++) {
            $currentSong = new CurrentSong(
                "id",
                "title",
                "artist",
                "imageUrl",
                $baseTime,
                $baseTime * 2
            );
            if ($baseTime == 0) {
                $this->assertEquals(0, $currentSong->getProgressPercent());
                continue;
            }
            $this->assertEquals(50, $currentSong->getProgressPercent());
        }
    }
}
