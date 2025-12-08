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

    getPlaybackStatus(address: string, token: string):PlaybackStatus {
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
                const episodeIndex = new URLSearchParams(window.location.search).get("ep_index");
                const seasonIndex = new URLSearchParams(window.location.search).get("season_index");
                const showId = new URLSearchParams(window.location.search).get("show_id");

                if (
                    id != item.PlayState.MediaSourceId ||
                    episodeIndex == null ||
                    episodeIndex == undefined ||
                    episodeIndex == "" ||
                    seasonIndex == null ||
                    seasonIndex == undefined ||
                    seasonIndex == "" ||
                    showId == null ||
                    showId == undefined ||
                    showId == ""
                ) {
                    JellyfinApi.getEpisodeWithParents(address, token, item.PlayState.MediaSourceId).then(
                        (response) => {
                            if (response.ok) {
                                response.json().then((data: EpisodeWithParentsInfo | null | undefined) => {
                                    if (data != null && data != undefined) {
                                        const episodeIndex = data.IndexNumber;
                                        const seasonIndex = data.ParentIndexNumber;

                                        window.location.search =
                                            "?ep_id=" +
                                            item.PlayState.MediaSourceId +
                                            "&ep_index=" +
                                            episodeIndex +
                                            "&season_index=" +
                                            seasonIndex +
                                            "&show_id=" +
                                            data.SeriesId;
                                        setTimeout(() => {
                                            window.location.reload();
                                        }, 100);
                                    }
                                });
                            } else {
                                alert("Failed to get episode info");
                            }
                        }
                    );
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
            })
            .catch(() => {
                this.changeTitle(
                    "Failed to connect to <a href='https://github.com/Wlczak/lylink-jellyfin'target='_blank'>lylink-jellyfin</a> server"
                );
                const params = new URLSearchParams(window.location.search);
                if (
                    params.get("ep_id") == null &&
                    params.get("ep_index") == null &&
                    params.get("season_index") == null &&
                    params.get("show_id") == null
                ) {
                    return;
                } else {
                    params.delete("ep_id");
                    params.delete("ep_index");
                    params.delete("season_index");
                    params.delete("show_id");
                    window.location.search = params.toString();
                }
            });
    }

    updateTime() {
        const progressBar = document.getElementById("progress-bar") as HTMLProgressElement;
        const progressTime = document.getElementById("progress-time") as HTMLSpanElement;

        this.progress_ticks += this.TICKS_PER_SECOND;
        const progressPercent = Number((this.progress_ticks * BigInt(100)) / this.duration_ticks);

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
        JellyfinApi.getEpisodeWithParents(address, token, mediaId)
            .then((response) => response.json())
            .then((data: EpisodeWithParentsInfo | null | undefined) => {
                if (data === null || data === undefined) {
                    placeholder();
                    return;
                }
                this.updateMediainfo(address, token, data);
                this.enableEdit(
                    data.Id,
                    data.SeasonId,
                    data.SeriesId,
                    data.IndexNumber,
                    data.ParentIndexNumber
                );
            });
    }

    updateMediainfo(address: string, token: string, info: EpisodeWithParentsInfo) {
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

        JellyfinApi.getItemImage(address, token, info.SeasonId, "Primary").then((response) => {
            if (response.ok === false) {
                JellyfinApi.getItemImage(address, token, info.SeriesId, "Primary").then((response) => {
                    response.bytes().then((binaryData) => {
                        this.setBlobAsCover(binaryData, response.headers.get("content-type") ?? "image/jpeg");
                    });
                });
            }
            response.bytes().then((binaryData) => {
                this.setBlobAsCover(binaryData, response.headers.get("content-type") ?? "image/jpeg");
            });
        });
    }

    enableEdit(epId: string, seasonId: string, showId: string, episodeIndex: number, seasonIndex: number) {
        const editContainer = document.getElementById("edit-container") as HTMLDivElement;
        const editBtn = document.getElementById("edit-btn") as HTMLAnchorElement;
        editBtn.href =
            "/lyrics/jellyfin/edit?ep_id=" +
            epId +
            "&season_id=" +
            seasonId +
            "&show_id=" +
            showId +
            "&ep_index=" +
            episodeIndex +
            "&season_index=" +
            seasonIndex;
        editContainer.hidden = false;
    }

    setBlobAsCover(binaryData: Uint8Array<ArrayBuffer>, contentType: string) {
        const blob = new Blob([binaryData], {
            type: contentType,
        });
        const img = document.getElementById("cover-image") as HTMLImageElement;
        img.src = URL.createObjectURL(blob);
        img.onload = () => {
            const width = img.naturalWidth;
            const height = img.naturalHeight;
            if (width != height) {
                img.style.width = "100%";
                img.style.height = "auto";
            }
        };
    }
}
