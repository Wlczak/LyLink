export {};

const jellyfinCard = document.getElementById("source-card-jellyfin");

await setUp();

async function setUp() {
    if (jellyfinCard != null) {
        const JellyfinApi = (await import("./jellyfinApi.js")).JellyfinApi;

        const serverAddressCookie = await cookieStore.get("jellyfin_server");
        const tokenCookie = await cookieStore.get("jellyfin_token");
        const serverAddress = decodeURIComponent(serverAddressCookie?.value ?? "");
        const token = tokenCookie?.value ?? "";
        JellyfinApi.getPlaybackInfo(serverAddress, token)
            .then((response) => response.json())
            .then((data: PlaybackInfo[] | null | undefined) => {
                if (data === null || data === undefined) {
                    const img = document.getElementById("src-img-jellyfin") as HTMLImageElement;
                    img.src = "/img/albumPlaceholer.svg";
                    changeTitle("Nothing is playing");
                    return;
                }

                const item = data[0];
                JellyfinApi.getEpisodeWithParents(serverAddress, token, item.PlayState.MediaSourceId).then(
                    (response) => {
                        if (response.ok) {
                            response.json().then((data: EpisodeWithParentsInfo | null | undefined) => {
                                if (data != null && data != undefined) {
                                    const episodeIndex = data.IndexNumber;
                                    const seasonIndex = data.ParentIndexNumber;

                                    const img = document.getElementById("src-img-jellyfin") as HTMLImageElement;
                                    img.src = "/img/albumPlaceholer.svg";
                                    changeTitle(data.SeriesName + " - S" + seasonIndex + "E" + episodeIndex);
                                    JellyfinApi.getItemImage(serverAddress, token, data.SeriesId, "Primary").then(
                                        (response) => {
                                            if (response.ok) {
                                                response.blob().then((blob) => {
                                                    img.src = URL.createObjectURL(blob);
                                                });
                                            }
                                        }
                                    );
                                }
                            });
                        } else {
                            alert("Failed to get episode info");
                        }
                    }
                );
            });
    }
}
function changeTitle(titleText: string) {
    const title = document.getElementById("src-title-jellyfin") as HTMLParagraphElement;
    title.innerHTML = titleText;
}
