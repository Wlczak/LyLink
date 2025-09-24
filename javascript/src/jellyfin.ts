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
                // getMediaInfo(address, token);
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
            console.log(data.Name);
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
