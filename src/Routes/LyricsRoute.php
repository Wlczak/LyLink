<?php

namespace Lylink\Routes;

use Closure;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;
use function Symfony\Component\String\s;
use Lylink\Auth\AuthSession;
use Lylink\Data\CurrentSong;
use Lylink\Data\EnvStore;
use Lylink\Data\LyricsData;
use Lylink\Data\Source;
use Lylink\DoctrineRegistry;
use Lylink\Interfaces\Datatypes\PlaybackInfo;
use Lylink\Interfaces\Datatypes\Track;
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
            SimpleRouter::get('/spotify/edit', [self::class, 'spotifyLyricsEdit']);
            SimpleRouter::post('/spotify/save', [self::class, 'updateSpotifyLyrics']);

            SimpleRouter::get('/jellyfin', [self::class, 'jellyfinLyrics']);
            SimpleRouter::get('/jellyfin/edit', [self::class, 'jellyfinEditList']);
            SimpleRouter::get('/jellyfin/edit/{id}', [self::class, 'jellyfinEditPage']);
            SimpleRouter::post('/jellyfin/save', [self::class, 'jellyfinUpdate']);
            SimpleRouter::get('/jellyfin/delete/{id}', [self::class, 'jellyfinDelete']);
        };
    }

    public static function lyricsHome(): string
    {
        $env = EnvStore::load();
        $auth = AuthSession::get();
        $sources = [];
        if ($auth === null) {
            header('Location: ' . $env->BASE_DOMAIN . '/login');
            die();
        }
        $user = $auth->getUser();
        if ($user === null) {
            header('Location: ' . $env->BASE_DOMAIN . '/login');
            die();
        }
        $id = $user->getId();
        if ($id === null) {
            header('Location: ' . $env->BASE_DOMAIN . '/login');
            die();
        }
        $settings = Settings::getSettings($id);

        if ($settings->spotify_connected) {
            $sources[] = new Source(id: 1, name: "Spotify", route: "/lyrics/spotify", current_song: new CurrentSong(id: "1", title: "Song", artist: "Artist", progress_ms: 5000, duration_ms: 100000));
        }

        if ($settings->jellyfin_connected) {
            $sources[] = new Source(id: 2, name: "Jellyfin", route: "/lyrics/jellyfin", current_song: new CurrentSong(id: "2", title: "Episode X", progress_ms: 5000, duration_ms: 100000));
        }

        return self::$twig->load('lyrics/lyrics_select.twig')->render(["auth" => $auth, "sources" => $sources]);
    }

    public static function jellyfinLyrics(): string
    {
        $env = EnvStore::load();
        $lyricsData = new LyricsData(name: "Loading...", is_playing: false, imageUrl: "/img/albumPlaceholer.svg");

        $settings = Settings::getSettings(AuthSession::get()?->getUser()?->getId() ?? 0);

        if ($settings->jellyfin_connected) {
            $address = $settings->jellyfin_server;
            $token = $settings->jellyfin_token;

            if (isset($_GET["show_id"]) && isset($_GET["season_index"]) && isset($_GET["ep_index"])) {
                /**
                 * @var string|float|int|bool|null
                 */
                $showId = $_GET["show_id"];
                $showId = strval($showId);
                $seasonIndex = $_GET["season_index"];
                $episodeIndex = $_GET["ep_index"];

                $em = DoctrineRegistry::get();
                $qb = $em->getRepository(Lyrics::class)->createQueryBuilder("l");

                $qb->where("l.jellyfinShowId = :showId");
                $qb->andWhere("l.jellyfinSeasonNumber = :seasonNumber");
                $qb->andWhere("l.jellyfinStartEpisodeNumber <= :episodeNumber");
                $qb->andWhere("l.jellyfinEndEpisodeNumber >= :episodeNumber");
                $qb->setParameters(new ArrayCollection([new Parameter("showId", $showId), new Parameter("seasonNumber", $seasonIndex), new Parameter("episodeNumber", $episodeIndex)]));

                /**
                 * @var array{Lyrics}|array{} $lyricsResults
                 */
                $lyricsResults = $qb->getQuery()->getResult();
                if (count($lyricsResults) > 0) {
                    /**
                     * @var Lyrics $lyrics
                     */
                    foreach ($lyricsResults as $key => $lyrics) {

                        $lyricsData->lyrics[$key] = $lyrics;
                        $lyricsData->id = $showId;
                    }
                }
            }

        } else {
            header('Location: ' . $env->BASE_DOMAIN . '/login');
            die();
        }

        return self::$twig->load('lyrics/jellyfin.twig')->render(["song" => $lyricsData,
            "address" => $address,
            "token" => $token,
            "allowEdit" => $settings->allow_edit]);
    }

    public static function jellyfinEditList(): string
    {
        $env = EnvStore::load();
        $settings = Settings::getSettings(AuthSession::get()?->getUser()?->getId() ?? 0);

        if ($settings->jellyfin_connected) {
            $address = $settings->jellyfin_server;
            $token = $settings->jellyfin_token;
        } else {
            header('Location: ' . $env->BASE_DOMAIN . '/login');
            die();
        }

        if (!$settings->allow_edit) {
            header('Location: ' . $env->BASE_DOMAIN . '/lyrics/jellyfin', );
            die();
        }

        $episodeId = $_GET["ep_id"];
        $showId = $_GET["show_id"];
        $seasonIndex = $_GET["season_index"];
        $episodeIndex = $_GET["ep_index"];

        $em = DoctrineRegistry::get();
        $qb = $em->getRepository(Lyrics::class)->createQueryBuilder("l");

        $qb->where("l.jellyfinShowId = :showId");
        $qb->andWhere("l.jellyfinSeasonNumber = :seasonNumber");
        $qb->andWhere("l.jellyfinStartEpisodeNumber <= :episodeNumber");
        $qb->andWhere("l.jellyfinEndEpisodeNumber >= :episodeNumber");
        $qb->setParameters(new ArrayCollection([new Parameter("showId", $showId), new Parameter("seasonNumber", $seasonIndex), new Parameter("episodeNumber", $episodeIndex)]));

        /**
         * @var array{Lyrics}|array{} $lyricsList
         */
        $lyricsList = $qb->getQuery()->getResult();

        return self::$twig->load('lyrics/jellyfin_edit_list.twig')->render([
            "address" => $address,
            "token" => $token,
            "lyrics_list" => $lyricsList,
            "ep_id" => $episodeId,
            "show_id" => $showId
        ]);
    }

    public static function jellyfinEditPage(string $idString): string
    {
        $env = EnvStore::load();
        $settings = Settings::getSettings(AuthSession::get()?->getUser()?->getId() ?? 0);

        if ($settings->jellyfin_connected) {
            $address = $settings->jellyfin_server;
            $token = $settings->jellyfin_token;
        } else {
            header('Location: ' . $env->BASE_DOMAIN . '/lyrics');
            die();
        }

        $showId = $_GET["show_id"];
        if ($showId === null || $showId === "" || $settings->allow_edit === false) {
            header('Location: ' . $env->BASE_DOMAIN . '/lyrics/jellyfin');
            die();
        }
        $id = intval($idString);

        $lyrics = "";
        $lyricsName = "";
        $seasonIndex = 1;
        $firstEpisodeIndex = 1;
        $lastEpisodeIndex = 1;
        if ($id !== 0) {
            $em = DoctrineRegistry::get();
            /**
             * @var Lyrics|null
             */
            $lyricsObj = $em->getRepository(Lyrics::class)->find($id);
            if ($lyricsObj !== null) {
                if ($lyricsObj->jellyfinShowId !== $showId) {
                    header('Location: ' . $env->BASE_DOMAIN . '/lyrics/jellyfin');
                    die();
                }
                $lyrics = $lyricsObj->lyrics;
                $lyricsName = $lyricsObj->jellyfinLyricsName;
                $seasonIndex = $lyricsObj->jellyfinSeasonNumber;
                $firstEpisodeIndex = $lyricsObj->jellyfinStartEpisodeNumber;
                $lastEpisodeIndex = $lyricsObj->jellyfinEndEpisodeNumber;
            }
        }
        return self::$twig->load('lyrics/jellyfin_edit.twig')->render([
            "address" => $address,
            "token" => $token,
            "lyrics" => $lyrics,
            "lyrics_name" => $lyricsName,
            "season_index" => $seasonIndex,
            "first_episode_index" => $firstEpisodeIndex,
            "last_episode_index" => $lastEpisodeIndex
        ]);
    }

    public static function jellyfinUpdate(): string
    {
        $input = file_get_contents('php://input');
        if ($input === false || $input === '') {
            http_response_code(400);
            return '';
        }

        /**
         * @var array{lyricsId:int,showId:string,seasonNumber:int,firstEpisode:int,lastEpisode:int,lyrics:string,lyricsName:string}
         */
        $json = json_decode($input, true);

        $lyricsId = $json['lyricsId'];

        $showId = $json['showId'];
        $seasonNumber = $json['seasonNumber'];
        $firstEpisode = $json['firstEpisode'];
        $lastEpisode = $json['lastEpisode'];
        $lyricsText = $json['lyrics'];
        $lyricsName = $json['lyricsName'];

        $settings = Settings::getSettings(AuthSession::get()?->getUser()?->getId() ?? 0);

        $auth = AuthSession::get();
        if ($auth === null) {
            http_response_code(500);
            return "";
        }
        if ($auth->isAuthorized() && $settings->allow_edit) {
            $entityManager = DoctrineRegistry::get();
            /**
             * @var Lyrics|null
             */
            $lyrics = $entityManager->getRepository(Lyrics::class)->findOneBy(['id' => $lyricsId]);
            var_dump($lyrics);
            if ($lyrics === null) {
                $lyrics = new Lyrics();
            }
            $lyrics->jellyfinShowId = $showId;
            $lyrics->jellyfinSeasonNumber = $seasonNumber;
            $lyrics->jellyfinStartEpisodeNumber = $firstEpisode;
            $lyrics->jellyfinEndEpisodeNumber = $lastEpisode;
            $lyrics->lyrics = $lyricsText;
            $lyrics->jellyfinLyricsName = $lyricsName;
            $entityManager->persist($lyrics);
            $entityManager->flush();

            return "ok";
        }
        http_response_code(500);
        return "";
    }

    public function jellyfinDelete(string $idString): void
    {
        $env = EnvStore::load();
        $auth = AuthSession::get()?->getUser()?->getId() ?? 0;
        if ($auth === 0) {
            header('Location: ' . $env->BASE_DOMAIN . '/login');
            die();
        }

        $id = intval($idString);
        if ($id === 0) {
            header('Location: ' . $env->BASE_DOMAIN . '/lyrics/jellyfin');
            die();
        }
        $em = DoctrineRegistry::get();
        /**
         * @var Lyrics|null
         */
        $lyrics = $em->getRepository(Lyrics::class)->find($id);
        $settings = Settings::getSettings(AuthSession::get()?->getUser()?->getId() ?? 0);

        if ($lyrics === null || $settings->allow_edit === false) {
            header('Location: ' . $env->BASE_DOMAIN . '/lyrics/jellyfin');
            die();
        }
        $em->remove($lyrics);
        $em->flush();
        header('Location: ' . $env->BASE_DOMAIN . '/lyrics/jellyfin');
    }

    public function spotifyLyrics(): void
    {
        $env = EnvStore::load();
        if (!isset($_SESSION['spotify_session'])) {
            header('Location: ' . $env->BASE_DOMAIN . '/integrations/spotify/callback');
        }

        /**
         * @var Session|null
         */
        $session = array_key_exists('spotify_session', $_SESSION) ? $_SESSION['spotify_session'] : null;

        if ($session === null) {
            header('Location: ' . $env->BASE_DOMAIN . '/integrations/spotify/callback');
            die();
        }

        if ($session->getTokenExpiration() < time()) {
            header('Location: ' . $env->BASE_DOMAIN . '/integrations/spotify/callback');
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
            if ($e->getMessage() === "Check settings on developer.spotify.com/dashboard, the user may not be registered.") {
                echo $template = self::$twig->load('whitelist.twig')->render();
                die();
            } else {
                throw $e;
            }
        }

        $settings = Settings::getSettings(AuthSession::get()?->getUser()?->getId() ?? 0);

        if ($info === null) {
            $song = [
                'name' => "No song is currently playing",
                'artist' => "",
                'duration' => 0,
                'duration_ms' => 0,
                'progress_ms' => 0,
                'imageUrl' => $env->BASE_DOMAIN . '/img/albumPlaceholer.svg',
                'id' => 0,
                'is_playing' => "false"
            ];

            echo $template = self::$twig->load('lyrics/spotify.twig')->render([
                'song' => $song,
                'allowEdit' => $settings->allow_edit
            ]);

        } else {

            if ($info->item === null) {
                die();
            }

            $id = $info->item->id;

            //echo $id;
            $entityManager = DoctrineRegistry::get();

            /**
             * @var Lyrics|null
             */
            $lyrics = $entityManager->getRepository(Lyrics::class)->findOneBy(['spotifyId' => $id]);

            if ($lyrics === null) {
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
                    'progressPercent' => $info->progress_ms / $info->item->duration_ms * 100,
                    'allowEdit' => $settings->allow_edit
                ]
            );
        }
    }

    public function spotifyLyricsEdit(): void
    {
        $env = EnvStore::load();
        $settings = Settings::getSettings(AuthSession::get()?->getUser()?->getId() ?? 0);
        if (!$settings->allow_edit) {
            header('Location: ' . $env->BASE_DOMAIN . '/lyrics/spotify', );
            die();
        }

        /**
         * @var Session
         */
        $session = $_SESSION['spotify_session'];
        /**
         * @var string|float|int|bool|null
         */
        $trackId = $_GET['id'];
        $trackId = strval($trackId);

        $api = new SpotifyWebAPI();
        $api->setAccessToken($session->getAccessToken());

        /**
         * @var Track
         */
        $track = $api->getTrack($trackId);

        $template = self::$twig->load('lyrics/spotify_edit.twig');

        $em = DoctrineRegistry::get();

        /**
         * @var Lyrics|null
         */
        $lyrics = $em->getRepository(Lyrics::class)->findOneBy(['spotifyId' => $trackId]);
        if ($lyrics === null) {
            $lyrics = new Lyrics();
        }

        echo $template->render([
            'song' => [
                'name' => $track->name,
                'artist' => $track->artists[0]->name,
                'imageUrl' => $track->album->images[0]->url,
                'duration' => $track->duration_ms,
                'id' => $track->id
            ],
            'lyrics' => $lyrics->lyrics
        ]);
    }

    public function updateSpotifyLyrics(): void
    {
        $env = EnvStore::load();
        $settings = Settings::getSettings(AuthSession::get()?->getUser()?->getId() ?? 0);
        if (!$settings->allow_edit) {
            header('Location: ' . $env->BASE_DOMAIN . '/lyrics/spotify', );
            die();
        }
        $entityManager = DoctrineRegistry::get();
        $lyrics = $entityManager->getRepository(Lyrics::class)->findOneBy(['spotifyId' => $_POST['id']]);
        if ($lyrics === null) {
            $lyrics = new Lyrics();
            /**
             * @var string|float|int|bool|null
             */
            $spotifyId = $_POST['id'];
            $lyrics->spotifyId = strval($spotifyId);
        }
        /**
         * @var string|float|int|bool|null
         */
        $lyricsString = $_POST['lyrics'];
        $lyrics->lyrics = strval($lyricsString);
        $entityManager->persist($lyrics);
        $entityManager->flush();
        header('Location: ' . $env->BASE_DOMAIN . '/lyrics/spotify');
    }
}
