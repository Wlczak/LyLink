export function start(address: string, token: string) {
    getPlaybackStatus(address, token);
    // getMediaInfo(address, token);
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
                return;
            }

            const item = data[0];
            const id = new URLSearchParams(window.location.search).get("ep_id");
            if (id == null || id != item.PlayState.MediaSourceId) {
                window.location.search = "?ep_id=" + item.PlayState.MediaSourceId;

                setTimeout(() => {
                    window.location.reload();
                }, 100);
            } else {
                getMediaInfo(address, token);
            }
        });
}
function getMediaInfo(address: string, token: string) {
    const mediaId = new URLSearchParams(window.location.search).get("ep_id");
    fetch(address + "/Item/" + mediaId, { method: "POST", body: JSON.stringify({ token: token }) })
        .then((response) => response.json())
        .then((data: MediaInfo | null | undefined) => {
            if (data === null || data === undefined) {
                const name = document.getElementById("name") as HTMLParagraphElement;
                const img = document.getElementById("cover-image") as HTMLImageElement;
                img.src = "/img/albumPlaceholer.svg";
                const nameValue = "Media does not exist";
                name.innerHTML = nameValue;
                return;
            }
            console.log(data.Name);
            updateMediainfo(data);
        });
}

function updateMediainfo(info: MediaInfo) {
    const name = document.getElementById("name") as HTMLParagraphElement;
    const fullName =
        info.SeriesName + " - " + "S" + info.ParentIndexNumber + "E" + info.IndexNumber + " - " + info.Name;
    name.innerHTML = fullName;
}
