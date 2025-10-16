interface EpisodeWithParentsInfo {
    Id: string;
    Name: string;
    Type: string;
    SeriesName: string;
    IndexNumber: number;
    ParentIndexNumber: number;
    ParentId: number;
    SeasonId: string;
    SeriesId: string;
}

interface SimpleSeason{
    Index: number
    Id: string
}

interface EpisodeInfo {
    Id: string;
    Name: string;
    Type: string;
    SeriesName: string;
    IndexNumber: number;
    ParentIndexNumber: number;
    ParentId: string;
}

interface SeasonInfo {
    Id: string;
    Name: string;
    Type: string;
    ParentId: string;
}

interface SeriesInfo {
    Id: string;
    Name: string;
    Type: string;
}

interface PlaybackInfo {
    NowPlayingItem: {
        RunTimeTicks: bigint;
    };
    PlayState: {
        PositionTicks: bigint;
        CanSeek: boolean;
        IsPaused: boolean;
        IsMuted: boolean;
        VolumeLevel: number;
        AudioStreamIndex: number;
        SubtitleStreamIndex: number;
        MediaSourceId: string;
        PlayMethod: string;
        PlaybackOrder: string;
        RepeatMode: string;
    };
}
