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

        const episodeList = await JellyfinApi.getEpisodeList(address, token, seasonInfo.ParentId);
        console.log(episodeInfo);
        console.log(seasonInfo);
        console.log(seriesInfo);

        this.setMediaInfo(episodeInfo, episodeList);
    }

    static setMediaInfo(episodeInfo: EpisodeInfo, episodeList: Array<EpisodeInfo> = []) {
        const seriesTitle = document.getElementById("series_title") as HTMLInputElement;
        const seasonsSelect = document.getElementById("season") as HTMLSelectElement;

        const seasonsList: Array<SimpleSeason> = [];
        episodeList.forEach((episode) => {
            if (seasonsList.find((season) => season.Index == episode.ParentIndexNumber) == undefined) {
                seasonsList.push({ Id: "", Index: episode.ParentIndexNumber });
            }
        });
        seasonsList.sort((a, b) => a.Index - b.Index);
        console.log(seasonsList);

        seasonsList.forEach((season) => {
            const option = document.createElement("option");
            option.text = "S" + season.Index.toString();
            option.value = episodeInfo.ParentId;
            option.id = "s-" + season.Index.toString();
            seasonsSelect.add(option);
        });

        const activeSeason = document.getElementById(
            "s-" + episodeInfo.ParentIndexNumber.toString()
        ) as HTMLOptionElement;
        activeSeason.selected = true;

        this.setEpisodeSelects(seasonsSelect.selectedOptions[0].innerText, episodeList);
        seasonsSelect.addEventListener("change", () => {
            this.setEpisodeSelects(seasonsSelect.selectedOptions[0].innerText, episodeList);
        });

        seriesTitle.value = episodeInfo.SeriesName;
    }

    static setEpisodeSelects(seasonIndexName: string, episodeList: Array<EpisodeInfo>) {
        const seasonIndex = parseInt(seasonIndexName.replace("S", ""));
        const episodeIndexList: number[] = [];
        const firstEpisodeSelect = document.getElementById("firstEpisodeSelect") as HTMLSelectElement;
        const lastEpisodeSelect = document.getElementById("lastEpisodeSelect") as HTMLSelectElement;
        firstEpisodeSelect.innerHTML = "";
        lastEpisodeSelect.innerHTML = "";

        episodeList.forEach((episode) => {
            if (episode.ParentIndexNumber == seasonIndex) {
                episodeIndexList.push(episode.IndexNumber);
            }
        });
        episodeIndexList.sort((a, b) => a - b);
        console.log(episodeIndexList);

        episodeIndexList.forEach((episodeIndex) => {
            const option = document.createElement("option");
            option.text = "E" + episodeIndex.toString();
            option.value = episodeIndex.toString();
            firstEpisodeSelect.add(option);
        });
        episodeIndexList.forEach((episodeIndex) => {
            const option = document.createElement("option");
            option.text = "E" + episodeIndex.toString();
            option.value = episodeIndex.toString();
            lastEpisodeSelect.add(option);
        });
    }
}
