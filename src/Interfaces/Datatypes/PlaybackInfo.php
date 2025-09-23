<?php

namespace Lylink\Interfaces\Datatypes;

abstract class PlaybackInfo
{
    public string $repeat_state;
    public bool $shuffle_state;
    public int $timestamp;
    public int $progress_ms;
    public bool $is_playing;
    public Track|null $item;
    public string $currently_playing_type;
}
