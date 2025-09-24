export function start() {
    let variables: Variables;
    if (!window.Variables) {
        console.error("Variables is not defined");
    } else {
        variables = window.Variables;
        const address = variables.address;
        const token = variables.token;
        getPlaybackStatus(address, token);
        // getMediaInfo(address, token);
    }
}

function getPlaybackStatus(address: string, token: string) {
    fetch(address + "/getPlaybackInfo", { method: "POST", body: JSON.stringify({ token: token }) })
        .then((response) => response.json())
        .then((data) => {
            const item = data[0];
            const id = new URLSearchParams(window.location.search).get("ep_id");
            if (id == null || id != item.PlayState.MediaSourceId) {
                window.location.search = "?ep_id=" + item.PlayState.MediaSourceId;

                setTimeout(() => {
                    window.location.reload();
                }, 100);
            }else{
                getMediaInfo(address, token);
            }
        });
}
function getMediaInfo(address: string, token: string) {
    fetch(address + "/Item", { method: "POST", body: JSON.stringify({ token: token }) })
        .then((response) => response.json())
        .then((data) => {
            const item = data[0];
            console.log(item);
        });
}
