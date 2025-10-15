import { JellyfinApi } from "./jellyfinApi.js";

export class JellyfinPlayback {
    playbackInterval: number = 0;
    refreshInterval: number = 0;

    progress_ticks: bigint = BigInt(0);
    duration_ticks: bigint = BigInt(0);
    readonly TICKS_PER_SECOND = BigInt(10000000);

    setUp(address: string, token: string) {
        this.getPlaybackStatus(address, token);
        this.refreshInterval = setInterval(() => this.getPlaybackStatus(address, token), 5000);
        this.getMediaInfo(address, token);
    }

    changeTitle(title: string) {
        const name = document.getElementById("name") as HTMLParagraphElement;
        document.title = "LyLink — " + title;
        name.innerHTML = title;
    }

    getPlaybackStatus(address: string, token: string) {
        JellyfinApi.getPlaybackInfo(address, token)
            .then((response) => response.json())
            .then((data: PlaybackInfo[] | null | undefined) => {
                if (data === null || data === undefined) {
                    const img = document.getElementById("cover-image") as HTMLImageElement;
                    img.src = "/img/albumPlaceholer.svg";
                    this.changeTitle("Nothing is playing");
                    if (new URLSearchParams(window.location.search).get("ep_id") != null) {
                        window.location.search = "";
                    }
                    return;
                }

                const item = data[0];
                const id = new URLSearchParams(window.location.search).get("ep_id");
                if (id != item.PlayState.MediaSourceId) {
                    window.location.search = "?ep_id=" + item.PlayState.MediaSourceId;
                    setTimeout(() => {
                        window.location.reload();
                    }, 100);
                } else {
                    const progressBar = document.getElementById("progress-bar") as HTMLProgressElement;
                    const progressTime = document.getElementById("progress-time") as HTMLSpanElement;
                    const totalTime = document.getElementById("total-time") as HTMLSpanElement;

                    const TICKS_PER_SECOND = BigInt(10000000);
                    const TICKS_PER_MINUTE = TICKS_PER_SECOND * BigInt(60);
                    const POSITION_TICKS = BigInt(item.PlayState.PositionTicks);
                    const RUN_TIME_TICKS = BigInt(item.NowPlayingItem.RunTimeTicks);

                    const cur_min = POSITION_TICKS / TICKS_PER_MINUTE;
                    const cur_sec = (POSITION_TICKS - cur_min * TICKS_PER_MINUTE) / TICKS_PER_SECOND;

                    const total_min = RUN_TIME_TICKS / TICKS_PER_MINUTE;
                    const total_sec = (RUN_TIME_TICKS - total_min * TICKS_PER_MINUTE) / TICKS_PER_SECOND;

                    const progressPercent = Number((POSITION_TICKS * BigInt(100)) / RUN_TIME_TICKS);
                    progressBar.value = progressPercent;
                    if (item.PlayState.IsPaused) {
                        clearInterval(this.playbackInterval);
                        progressBar.classList.add("is-warning");
                        progressBar.classList.remove("is-primary");
                    } else {
                        if (this.playbackInterval != 0) {
                            clearInterval(this.playbackInterval);
                        }
                        this.playbackInterval = setInterval(() => {
                            this.updateTime();
                        }, 1000);
                        progressBar.classList.add("is-primary");
                        progressBar.classList.remove("is-warning");
                    }

                    progressTime.innerHTML = `${cur_min < 10 ? "0" + cur_min : cur_min}:${
                        cur_sec < 10 ? "0" + cur_sec : cur_sec
                    }`;
                    totalTime.innerHTML = `${total_min < 10 ? "0" + total_min : total_min}:${
                        total_sec < 10 ? "0" + total_sec : total_sec
                    }`;

                    this.progress_ticks = POSITION_TICKS;
                    this.duration_ticks = RUN_TIME_TICKS;
                }
            });
    }

    updateTime() {
        const progressBar = document.getElementById("progress-bar") as HTMLProgressElement;
        const progressTime = document.getElementById("progress-time") as HTMLSpanElement;

        this.progress_ticks += this.TICKS_PER_SECOND;
        const progressPercent = Number((this.progress_ticks * BigInt(100)) / this.duration_ticks);

        console.log(progressPercent);
        progressBar.value = progressPercent;

        const cur_min = this.progress_ticks / this.TICKS_PER_SECOND / BigInt(60);
        const cur_sec = this.progress_ticks / this.TICKS_PER_SECOND - cur_min * BigInt(60);
        progressTime.innerHTML =
            cur_min.toString().padStart(2, "0") + ":" + cur_sec.toString().padStart(2, "0");

        if (this.progress_ticks >= this.duration_ticks) {
            window.location.reload();
        }
    }

    getMediaInfo(address: string, token: string) {
        const placeholder = () => {
            const img = document.getElementById("cover-image") as HTMLImageElement;
            img.src = "/img/albumPlaceholer.svg";
            this.changeTitle("Media does not exist");
            return;
        };
        const mediaId = new URLSearchParams(window.location.search).get("ep_id");
        if (mediaId == null || mediaId == undefined || mediaId == "") {
            placeholder();
            return;
        }
        JellyfinApi.getItem(address, token, mediaId)
            .then((response) => response.json())
            .then((data: EpisodeInfo | null | undefined) => {
                if (data === null || data === undefined) {
                    placeholder();
                    return;
                }
                this.updateMediainfo(data);
                this.enableEdit(data.Id, data.SeriesId, data.SeriesId);
            });
    }

    updateMediainfo(info: EpisodeInfo) {
        const fullName =
            info.SeriesName +
            " - " +
            "S" +
            String(info.ParentIndexNumber).padStart(2, "0") +
            "E" +
            String(info.IndexNumber).padStart(2, "0") +
            " - " +
            info.Name;
        this.changeTitle(fullName);
    }

    enableEdit(epId: string, seasonId: string, showId: string) {
        const editContainer = document.getElementById("edit-container") as HTMLDivElement;
        const editBtn = document.getElementById("edit-btn") as HTMLAnchorElement;
        editBtn.href =
            "/lyrics/jellyfin/edit?ep_id=" + epId + "&season_id=" + seasonId + "&show_id=" + showId;
        editContainer.hidden = false;
    }
}
