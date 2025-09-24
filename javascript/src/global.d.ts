interface MediaInfo {
    Name: string;
    Type: string;
    SeriesName: string;
    IndexNumber: number;
    ParentIndexNumber: number;
}


interface PlaybackInfo {
  RunTimeTicks: number
  PlayState: {
    PositionTicks: number
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
