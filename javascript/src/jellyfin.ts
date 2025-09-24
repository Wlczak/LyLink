export function start(address: string, token: string) {
    getPlaybackStatus(address, token);
    setInterval(() => getPlaybackStatus(address, token), 5000);
    getMediaInfo(address, token);
}

function getPlaybackStatus(address: string, token: string) {
    fetch(address + "/getPlaybackInfo", { method: "POST", body: JSON.stringify({ token: token }) })
        .then((response) => response.json())
        .then((data: PlaybackInfo[] | null | undefined) => {
            if (data === null || data === undefined) {
                const name = document.getElementById("name") as HTMLParagraphElement;
                const img = document.getElementById("cover-image") as HTMLImageElement;
                img.src = "/img/albumPlaceholer.svg";
                const nameValue = "Nothing is playing";
                name.innerHTML = nameValue;
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

                const cur_min = (item.PlayState.PositionTicks / 60000000000) | 0;
                const cur_sec = (item.PlayState.PositionTicks - cur_min * 60000000000) / 10000000;
                const total_min = (item.RunTimeTicks / 60000000000) | 0;
                const total_sec = (item.RunTimeTicks - total_min * 60000000000) / 10000000;

                const progressPercent = (item.PlayState.PositionTicks * 100) / item.RunTimeTicks;
                console.log(progressPercent, Number.isFinite(progressPercent));
                progressBar.value = 50;
                progressTime.innerHTML = `${cur_min < 10 ? "0" + cur_min : cur_min}:${
                    cur_sec < 10 ? "0" + cur_sec : cur_sec
                }`;
                totalTime.innerHTML = `${total_min < 10 ? "0" + total_min : total_min}:${
                    total_sec < 10 ? "0" + total_sec : total_sec
                }`;
            }
        });
}
function getMediaInfo(address: string, token: string) {
    const placeholder = () => {
        const name = document.getElementById("name") as HTMLParagraphElement;
        const img = document.getElementById("cover-image") as HTMLImageElement;
        img.src = "/img/albumPlaceholer.svg";
        const nameValue = "Media does not exist";
        name.innerHTML = nameValue;
        return;
    };
    const mediaId = new URLSearchParams(window.location.search).get("ep_id");
    if (mediaId == null || mediaId == undefined || mediaId == "") {
        placeholder();
        return;
    }
    fetch(address + "/Item/" + mediaId, { method: "POST", body: JSON.stringify({ token: token }) })
        .then((response) => response.json())
        .then((data: MediaInfo | null | undefined) => {
            if (data === null || data === undefined) {
                placeholder();
                return;
            }
            updateMediainfo(data);
        });
}

function updateMediainfo(info: MediaInfo) {
    const name = document.getElementById("name") as HTMLParagraphElement;
    const fullName =
        info.SeriesName +
        " - " +
        "S" +
        String(info.ParentIndexNumber).padStart(2, "0") +
        "E" +
        String(info.IndexNumber).padStart(2, "0") +
        " - " +
        info.Name;
    name.innerHTML = fullName;
}
