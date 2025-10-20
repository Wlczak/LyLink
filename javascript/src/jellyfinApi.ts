export class JellyfinApi {
    static getPlaybackInfo(address: string, token: string): Promise<Response> {
        return fetch(address + "/getPlaybackInfo", {
            method: "POST",
            body: JSON.stringify({ token: token }),
        });
    }

    static getEpisodeWithParents(address: string, token: string, mediaId: string): Promise<Response> {
        return fetch(address + "/Episode/WithParents/" + mediaId, {
            method: "POST",
            body: JSON.stringify({ token: token }),
        });
    }

    static async getEpisodeInfo(address: string, token: string, mediaId: string): Promise<EpisodeInfo> {
        const res = await fetch(address + "/Episode/" + mediaId, {
            method: "POST",
            body: JSON.stringify({ token: token }),
        });
        return res.json();
    }

    static async getSeasonInfo(address: string, token: string, mediaId: string): Promise<SeasonInfo> {
        const res = await fetch(address + "/Season/" + mediaId, {
            method: "POST",
            body: JSON.stringify({ token: token }),
        });
        return res.json();
    }

    static async getSeriesInfo(address: string, token: string, mediaId: string): Promise<SeriesInfo> {
        const res = await fetch(address + "/Series/" + mediaId, {
            method: "POST",
            body: JSON.stringify({ token: token }),
        });
        return res.json();
    }

    static async getEpisodeList(
        address: string,
        token: string,
        seriesId: string
    ): Promise<Array<EpisodeInfo>> {
        const res = await fetch(address + "/Series/" + seriesId + "/ListSeasonsAndEpisodes", {
            method: "POST",
            body: JSON.stringify({ token: token }),
        });
        return res.json();
    }

    static async saveJellyfinLyrics(
        showId: string,
        seasonNumber: number,
        firstEpisode: number,
        lastEpisode: number,
        lyrics: string
    ) {
        console.log(JSON.stringify({ showId, seasonNumber, firstEpisode, lastEpisode, lyrics }));
        fetch("/lyrics/jellyfin/edit", {
            method: "POST",
            body: JSON.stringify({ showId, seasonNumber, firstEpisode, lastEpisode, lyrics }),
        }).then((res) => {
            if (res.ok) {
                window.location.replace("/lyrics/jellyfin");
            } else {
                alert("Failed to save lyrics");
            }
        });
    }

    static async getItemImage(
        address: string,
        token: string,
        mediaId: string,
        imageType: string
    ): Promise<Response> {
        const res = await fetch(address + "/GetImage/" + mediaId + "/" + imageType, {
            method: "POST",
            body: JSON.stringify({ token: token }),
        });
        return res;
    }
}
