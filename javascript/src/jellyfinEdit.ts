import { JellyfinApi } from "./jellyfinApi.js";

export class JellyfinEdit {
    static async setUp(
        address: string,
        token: string,
        seasonIndex: string,
        firstEpisodeIndex: string,
        lastEpisodeIndex: string
    ) {
        const mediaId = new URLSearchParams(window.location.search).get("ep_id");
        if (mediaId == null || mediaId == undefined || mediaId == "") {
            console.error("No mediaId found");
            window.location.replace("/lyrics/jellyfin");
            return;
        }

        const episodeInfo = await JellyfinApi.getEpisodeInfo(address, token, mediaId);
        const seasonInfo = await JellyfinApi.getSeasonInfo(address, token, episodeInfo.ParentId);
        // const seriesInfo = await JellyfinApi.getSeriesInfo(address, token, seasonInfo.ParentId);

        const episodeList = await JellyfinApi.getEpisodeList(address, token, seasonInfo.ParentId);

        this.setMediaInfo(episodeInfo, episodeList, seasonIndex, firstEpisodeIndex, lastEpisodeIndex);

        const imageUrl = await this.getImageBlobUrl(address, token, episodeInfo.ParentId);

        const posterImage = document.getElementById("posterImage") as HTMLImageElement;
        posterImage.src = imageUrl;

        const form = document.getElementById("lyricsForm") as HTMLFormElement;
        form.addEventListener("submit", this.saveForm);
    }

    static async setUpList(address: string, token: string) {
        const episodeId = new URLSearchParams(window.location.search).get("ep_id");
        const seasonId = new URLSearchParams(window.location.search).get("season_id");
        if (
            episodeId == null ||
            episodeId == undefined ||
            episodeId == "" ||
            seasonId == null ||
            seasonId == undefined ||
            seasonId == ""
        ) {
            console.error("No mediaId found");
            window.location.replace("/lyrics/jellyfin");
            return;
        }
        try {
            const episodeInfo = await JellyfinApi.getEpisodeInfo(address, token, episodeId);

            const episodeInfoElement = document.getElementById("episode_info") as HTMLSpanElement;
            const episodeSeasonInfoElement = document.getElementById(
                "episode-season-info"
            ) as HTMLSpanElement;

            episodeInfoElement.textContent =
                episodeInfo.SeriesName +
                " — S" +
                episodeInfo.ParentIndexNumber.toString() +
                "E" +
                episodeInfo.IndexNumber.toString();

            episodeSeasonInfoElement.textContent =
                "S" + episodeInfo.ParentIndexNumber.toString() + "E" + episodeInfo.IndexNumber.toString();

            document.title =
                "LyLink — " +
                episodeInfo.SeriesName +
                " — S" +
                episodeInfo.ParentIndexNumber.toString() +
                "E" +
                episodeInfo.IndexNumber.toString();

            const blobUrl = await this.getImageBlobUrl(address, token, seasonId);
            const episodeImageElement = document.getElementById("episode-poster") as HTMLImageElement;
            episodeImageElement.src = blobUrl;
        } catch (e) {
            console.error(e);
            window.location.replace("/lyrics/jellyfin");
            return;
        }
    }

    static async getImageBlobUrl(address: string, token: string, mediaId: string): Promise<string> {
        const response = await JellyfinApi.getItemImage(address, token, mediaId, "Primary");
        if (response.ok) {
            return URL.createObjectURL(await response.blob());
        }
        return "";
    }

    static setMediaInfo(
        episodeInfo: EpisodeInfo,
        episodeList: Array<EpisodeInfo> = [],
        seasonIndex: string,
        firstEpisodeIndex: string,
        lastEpisodeIndex: string
    ) {
        const seriesTitle = document.getElementById("series_title") as HTMLInputElement;
        const seasonsSelect = document.getElementById("season") as HTMLSelectElement;

        const seasonsList: Array<SimpleSeason> = [];
        episodeList.forEach((episode) => {
            if (seasonsList.find((season) => season.Index == episode.ParentIndexNumber) == undefined) {
                seasonsList.push({ Id: "", Index: episode.ParentIndexNumber });
            }
        });
        seasonsList.sort((a, b) => a.Index - b.Index);

        seasonsList.forEach((season) => {
            const option = document.createElement("option");
            option.text = "S" + season.Index.toString();
            option.value = episodeInfo.ParentId;
            option.id = "s-" + season.Index.toString();
            
            option.selected = season.Index.toString() === seasonIndex;
            seasonsSelect.add(option);
        });

        const activeSeason = document.getElementById(
            "s-" + episodeInfo.ParentIndexNumber.toString()
        ) as HTMLOptionElement;
        activeSeason.selected = true;

        this.setEpisodeSelects(
            seasonsSelect.selectedOptions[0].innerText,
            episodeList,
            episodeInfo.IndexNumber
        );
        seasonsSelect.addEventListener("change", () => {
            this.setEpisodeSelects(
                seasonsSelect.selectedOptions[0].innerText,
                episodeList,
                episodeInfo.IndexNumber
            );
        });

        seriesTitle.value = episodeInfo.SeriesName;

        const firstEpisodeSelect = document.getElementById("firstEpisodeSelect") as HTMLSelectElement;
        const lastEpisodeSelect = document.getElementById("lastEpisodeSelect") as HTMLSelectElement;

        Array.from(firstEpisodeSelect.options).forEach((option) => {
            // option.value is a string, so convert targetValue to string
            option.selected = option.value === firstEpisodeIndex.toString();
        });
        Array.from(lastEpisodeSelect.options).forEach((option) => {
            // option.value is a string, so convert targetValue to string
            option.selected = option.value === lastEpisodeIndex.toString();
        });
    }

    static setEpisodeSelects(
        seasonIndexName: string,
        episodeList: Array<EpisodeInfo>,
        currentEpisodeIndex: number
    ) {
        const seasonIndex = parseInt(seasonIndexName.replace("S", ""));
        const episodeIndexList: number[] = [];
        const firstEpisodeSelect = document.getElementById("firstEpisodeSelect") as HTMLSelectElement;
        const lastEpisodeSelect = document.getElementById("lastEpisodeSelect") as HTMLSelectElement;
        firstEpisodeSelect.innerHTML = "";
        lastEpisodeSelect.innerHTML = "";

        firstEpisodeSelect.removeEventListener("change", this.episodeSelectValidate);
        firstEpisodeSelect.addEventListener("change", this.episodeSelectValidate);
        lastEpisodeSelect.removeEventListener("change", this.episodeSelectValidate);
        lastEpisodeSelect.addEventListener("change", this.episodeSelectValidate);

        episodeList.forEach((episode) => {
            if (episode.ParentIndexNumber == seasonIndex) {
                episodeIndexList.push(episode.IndexNumber);
            }
        });
        episodeIndexList.sort((a, b) => a - b);

        episodeIndexList.forEach((episodeIndex) => {
            const option = document.createElement("option");
            option.text = "E" + episodeIndex.toString();
            option.value = episodeIndex.toString();
            if (episodeIndex == currentEpisodeIndex) {
                option.selected = true;
            }
            firstEpisodeSelect.add(option);
        });
        episodeIndexList.forEach((episodeIndex) => {
            const option = document.createElement("option");
            option.text = "E" + episodeIndex.toString();
            option.value = episodeIndex.toString();
            if (episodeIndex == currentEpisodeIndex) {
                option.selected = true;
            }
            lastEpisodeSelect.add(option);
        });
    }

    static episodeSelectValidate() {
        const firstEpisodeSelect = document.getElementById("firstEpisodeSelect") as HTMLSelectElement;
        const lastEpisodeSelect = document.getElementById("lastEpisodeSelect") as HTMLSelectElement;
        const lastParent = lastEpisodeSelect.parentNode as HTMLDivElement;
        const saveButton = document.getElementById("saveBtn") as HTMLButtonElement;

        if (firstEpisodeSelect.selectedIndex > lastEpisodeSelect.selectedIndex) {
            lastParent.classList.add("is-danger");
            lastParent.title = "First episode must be before or the same as last episode";
            saveButton.disabled = true;
        } else {
            lastParent.classList.remove("is-danger");
            lastParent.title = "";
            saveButton.disabled = false;
        }
    }

    static saveForm(e: Event) {
        e.preventDefault();
        const seasonInput = document.getElementById("season") as HTMLSelectElement;
        const firstEpisodeSelect = document.getElementById("firstEpisodeSelect") as HTMLSelectElement;
        const lastEpisodeSelect = document.getElementById("lastEpisodeSelect") as HTMLSelectElement;
        const lyricsInput = document.getElementById("lyricsInput") as HTMLInputElement;

        const showId = new URLSearchParams(window.location.search).get("show_id");
        const seasonNumber = Number(seasonInput.selectedOptions[0].innerText.replace("S", ""));
        const firstEpisode = Number(firstEpisodeSelect.value);
        const lastEpisode = Number(lastEpisodeSelect.value);
        const lyrics = lyricsInput.value;

        if (showId == null || showId == undefined || showId == "") {
            alert("Invalid URL.");
            window.location.replace("/lyrics/jellyfin");
            return;
        }

        JellyfinApi.saveJellyfinLyrics(showId, seasonNumber, firstEpisode, lastEpisode, lyrics);
    }
}
