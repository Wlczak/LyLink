interface MediaInfo {
    Name: string;
    Type: string;
    SeriesName: string;
    IndexNumber: number;
    ParentIndexNumber: number;
}


interface PlaybackInfo {
  RunTimeTicks: bigint
  PlayState: {
    PositionTicks: bigint
    CanSeek: boolean
    IsPaused: boolean
    IsMuted: boolean
    VolumeLevel: number
    AudioStreamIndex: number
    SubtitleStreamIndex: number
    MediaSourceId: string
    PlayMethod: string
    PlaybackOrder: string
    RepeatMode: string
  }
}
