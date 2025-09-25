<?php

namespace Lylink\Routes;

use Closure;
use Lylink\Auth\AuthSession;
use Lylink\Data\CurrentSong;
use Lylink\Data\LyricsData;
use Lylink\Data\Source;
use Lylink\DoctrineRegistry;
use Lylink\Interfaces\Datatypes\PlaybackInfo;
use Lylink\Interfaces\Routing\Route;
use Lylink\Models\Lyrics;
use Lylink\Models\Settings;
use Lylink\Router;
use Pecee\SimpleRouter\SimpleRouter;
use SpotifyWebAPI\Session;
use SpotifyWebAPI\SpotifyWebAPI;
use SpotifyWebAPI\SpotifyWebAPIException;

class LyricsRoute extends Router implements Route
{
    public static function setup(): Closure
    {
        return function () {
            SimpleRouter::get('/', [self::class, 'lyricsHome']);
            SimpleRouter::get('/spotify', [self::class, 'spotifyLyrics']);
            SimpleRouter::post('/spotify', [self::class, 'update']);

            SimpleRouter::get('/jellyfin', [self::class, 'jellyfinLyrics']);
        };
    }

    public static function lyricsHome(): string
    {
        $auth = AuthSession::get();
        $sources = [];
        if ($auth == null) {
            header('Location: ' . $_ENV['BASE_DOMAIN'] . '/login');
            die();
        }
        $user = $auth->getUser();
        if ($user == null) {
            header('Location: ' . $_ENV['BASE_DOMAIN'] . '/login');
            die();
        }
        $id = $user->getId();
        if ($id === null) {
            header('Location: ' . $_ENV['BASE_DOMAIN'] . '/login');
            die();
        }
        $settings = Settings::getSettings($id);

        if ($settings->spotify_connected) {
            $sources[] = new Source(id: 1, name: "Spotify", route: "/lyrics/spotify", current_song: new CurrentSong(id: "1", title: "Test song", artist: "Test artist", progress_ms: 5000, duration_ms: 100000));
        }

        if ($settings->jellyfin_connected) {
            $sources[] = new Source(id: 2, name: "Jellyfin", route: "/lyrics/jellyfin", current_song: new CurrentSong(id: "2", title: "Episode X", progress_ms: 5000, duration_ms: 100000));
        }

        return self::$twig->load('lyrics/lyrics_select.twig')->render(["auth" => $auth, "sources" => $sources]);
    }

    public static function jellyfinLyrics(): string
    {
        $lyricsData = new LyricsData(name: "Loading...", is_playing: false);

        $settings = Settings::getSettings(AuthSession::get()?->getUser()?->getId() ?? 0);

        if ($settings->jellyfin_connected) {
            $address = $settings->jellyfin_server;
            $token = $settings->jellyfin_token;
        } else {
            header('Location: ' . $_ENV['BASE_DOMAIN'] . '/login');
            die();
        }

        return self::$twig->load('lyrics/jellyfin.twig')->render(["song" => $lyricsData, "address" => $address, "token" => $token]);
    }

    public function spotifyLyrics(): void
    {
        if (!isset($_SESSION['spotify_session'])) {
            header('Location: ' . $_ENV['BASE_DOMAIN'] . '/callback');
        }

        /**
         * @var Session|null
         */
        $session = $_SESSION['spotify_session'];

        if ($session == null) {
            header('Location: ' . $_ENV['BASE_DOMAIN'] . '/callback');
            die();
        }

        if ($session->getTokenExpiration() < time()) {
            header('Location: ' . $_ENV['BASE_DOMAIN'] . '/callback');
            die();
        }

        $api = new SpotifyWebAPI();
        $api->setAccessToken($session->getAccessToken());

        try {
            /**
             * @var PlaybackInfo|null
             */
            $info = $api->getMyCurrentPlaybackInfo();
        } catch (SpotifyWebAPIException $e) {
            if ($e->getMessage() == "Check settings on developer.spotify.com/dashboard, the user may not be registered.") {
                echo $template = self::$twig->load('whitelist.twig')->render();
                die();
            } else {
                throw $e;
            }
        }
        if ($info == null) {
            $song = [
                'name' => "No song is currently playing",
                'artist' => "",
                'duration' => 0,
                'duration_ms' => 0,
                'progress_ms' => 0,
                'imageUrl' => $_ENV['BASE_DOMAIN'] . '/img/albumPlaceholer.svg',
                'id' => 0,
                'is_playing' => "false"
            ];

            echo $template = self::$twig->load('lyrics/spotify.twig')->render([
                'song' => $song
            ]);

        } else {

            if ($info->item == null) {
                die();
            }

            $id = $info->item->id;

            //echo $id;
            $entityManager = DoctrineRegistry::get();

            /**
             * @var Lyrics|null
             */
            $lyrics = $entityManager->getRepository(Lyrics::class)->findOneBy(['spotify_id' => $id]);

            if ($lyrics == null) {
                $lyrics = new Lyrics();
            }

            $template = self::$twig->load('lyrics/spotify.twig');

            $song = [
                'name' => $info->item->name,
                'artist' => $info->item->artists[0]->name,
                'duration' => $info->item->duration_ms / 1000,
                'duration_ms' => $info->item->duration_ms,
                'progress_ms' => $info->progress_ms,
                'imageUrl' => $info->item->album->images[0]->url,
                'id' => $info->item->id,
                'is_playing' => $info->is_playing
            ];
            echo $template->render(
                [
                    'lyrics' => $lyrics->lyrics,
                    'song' => $song,
                    'progressPercent' => $info->progress_ms / $info->item->duration_ms * 100]
            );
        }
    }
}
