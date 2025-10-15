import { JellyfinApi } from "./jellyfinApi.js";

export class JellyfinEdit {
    static async setUp(address: string, token: string) {
        const mediaId = new URLSearchParams(window.location.search).get("ep_id");
        if (mediaId == null || mediaId == undefined || mediaId == "") {
            console.error("No mediaId found");
            return;
        }

        const episodeInfo = await JellyfinApi.getEpisodeInfo(address, token, mediaId);
        const seasonInfo = await JellyfinApi.getSeasonInfo(address, token, episodeInfo.ParentId);
        const seriesInfo = await JellyfinApi.getSeriesInfo(address, token, seasonInfo.ParentId);
        console.log(episodeInfo);
        console.log(seasonInfo);
        console.log(seriesInfo);

        

        this.setMediaInfo(episodeInfo);
    }

    static setMediaInfo(episodeInfo: EpisodeInfo) {
        const seriesTitle = document.getElementById("series_title") as HTMLInputElement;
        seriesTitle.value = episodeInfo.SeriesName;
    }
}
