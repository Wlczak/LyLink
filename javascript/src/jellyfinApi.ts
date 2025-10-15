export class JellyfinApi {
    static getPlaybackInfo(address: string, token: string): Promise<Response> {
        return fetch(address + "/getPlaybackInfo", {
            method: "POST",
            body: JSON.stringify({ token: token }),
        });
    }

    static getItem(address: string, token: string, mediaId: string): Promise<Response> {
        return fetch(address + "/Item/" + mediaId, { method: "POST", body: JSON.stringify({ token: token }) });
    }
}
