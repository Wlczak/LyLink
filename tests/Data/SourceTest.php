<?php

use Lylink\Data\CurrentSong;
use Lylink\Data\Source;
use PHPUnit\Framework\TestCase;

final class SourceTest extends TestCase
{
    public function testConstructor(): void
    {
        $id = 0;
        $name = "test";
        $route = "test";
        $current_song = new CurrentSong();
        $source = new Source($id, $name, $route, $current_song);
        $this->assertEquals($id, $source->id);
        $this->assertEquals($name, $source->name);
        $this->assertEquals($route, $source->route);
        $this->assertEquals($current_song, $source->current_song);
    }

    public function testConstructorWithNullParam(): void
    {
        $source = new Source(0, "test", "test", null);
        $this->assertSame(null, $source->current_song);
    }

    public function testDefaultConstructorValues(): void
    {
        $id = 0;
        $name = "test";
        $route = "test";
        $source = new Source($id, $name, $route);
        $this->assertEquals($id, $source->id);
        $this->assertEquals($name, $source->name);
        $this->assertEquals($route, $source->route);

        $this->assertNull($source->current_song);
    }

    public function testHasSong(): void
    {
        $source = new Source(0, "test", "test", new CurrentSong());
        $this->assertTrue($source->hasSong());

        $source = new Source(0, "test", "test", null);
        $this->assertFalse($source->hasSong());
    }
}
