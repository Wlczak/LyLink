<?php

namespace Tests\Routes;

use Lylink\Auth\AuthSession;
use Lylink\Auth\DefaultAuth;
use Lylink\DoctrineRegistry;
use Lylink\Models\Lyrics;
use Lylink\Models\Settings;
use Lylink\Models\User;
use Lylink\Router;
use Lylink\Routes\LyricsRoute;
use PHPUnit\Framework\TestCase;
use Tests\Helpers\TestDatabaseHelper;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class LyricsRouteCoverageTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION = [];
        $_POST = [];
        $_GET = [];
        $_ENV['SMTP_HOST'] = 'smtp.test.test';
        $_ENV['SMTP_USERNAME'] = 'test@test.test';
        $_ENV['SMTP_PASSWORD'] = 'secret';
        $_ENV['CLIENT_ID'] = 'client';
        $_ENV['CLIENT_SECRET'] = 'secret';
        $_ENV['BASE_DOMAIN'] = 'base.test';
        \SpotifyWebAPI\SpotifyWebAPI::$nextPlaybackInfo = null;
        \SpotifyWebAPI\SpotifyWebAPI::$nextTrack = null;
        \SpotifyWebAPI\SpotifyWebAPI::$nextMe = ['display_name' => 'Test User'];
        \SpotifyWebAPI\Session::$nextAccessToken = 'token';
        \SpotifyWebAPI\Session::$nextTokenExpiration = 9999999999;
        TestDatabaseHelper::createTestDatabase();
        Router::$twig = new Environment(new ArrayLoader([
            'lyrics/lyrics_select.twig' => 'select {{ sources|length }}',
            'lyrics/jellyfin.twig' => 'jellyfin {{ song.id|default("") }} {{ song.lyrics|length }} {{ allowEdit ? "yes" : "no" }}',
            'lyrics/jellyfin_edit_list.twig' => 'list {{ lyrics_list|length }} {{ ep_id }} {{ show_id }}',
            'lyrics/jellyfin_edit.twig' => 'edit {{ lyrics }} {{ lyrics_name }} {{ season_index }} {{ first_episode_index }} {{ last_episode_index }}',
            'lyrics/spotify.twig' => 'spotify {{ song.name }} {{ song.id }} {{ lyrics|default("") }} {{ progressPercent|default(0) }} {{ allowEdit ? "yes" : "no" }}',
            'lyrics/spotify_edit.twig' => 'spedit {{ song.id }} {{ lyrics|default("") }}',
            'whitelist.twig' => 'whitelist',
        ]));
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_POST = [];
        $_GET = [];
        session_destroy();
        TestDatabaseHelper::dropTestDatabase();
    }

    public function testLyricsHomeShowsConnectedSources(): void
    {
        $auth = $this->createAuthorizedUser('home@test.test', 'home');
        $settings = Settings::getSettings($auth->getUser()?->getId() ?? 0);
        $settings->spotify_connected = true;
        $settings->jellyfin_connected = true;

        $result = LyricsRoute::lyricsHome();

        $this::assertStringContainsString('select 2', $result);
    }

    public function testJellyfinLyricsEditListPageAndDelete(): void
    {
        $auth = $this->createAuthorizedUser('jelly@test.test', 'jelly');
        $settings = Settings::getSettings($auth->getUser()?->getId() ?? 0);
        $settings->jellyfin_connected = true;
        $settings->allow_edit = true;
        $settings->jellyfin_server = 'http://jellyfin.test';
        $settings->jellyfin_token = 'token';

        $lyrics = new Lyrics();
        $lyrics->jellyfinShowId = 'show-1';
        $lyrics->jellyfinSeasonNumber = 1;
        $lyrics->jellyfinStartEpisodeNumber = 1;
        $lyrics->jellyfinEndEpisodeNumber = 3;
        $lyrics->jellyfinLyricsName = 'Episode One';
        $lyrics->lyrics = 'line 1';
        DoctrineRegistry::get()->persist($lyrics);
        DoctrineRegistry::get()->flush();
        $lyricsId = $lyrics->getId();
        $route = new LyricsRoute();

        $_GET = ['show_id' => 'show-1', 'season_index' => 1, 'ep_index' => 2, 'ep_id' => 'ep-1'];
        $list = LyricsRoute::jellyfinEditList();
        $this::assertStringContainsString('list 1 ep-1 show-1', $list);

        $page = LyricsRoute::jellyfinEditPage((string) $lyricsId);
        $this::assertStringContainsString('edit line 1 Episode One 1 1 3', $page);

        $route->jellyfinDelete((string) $lyricsId);
        $deleted = DoctrineRegistry::get()->getRepository(Lyrics::class)->find($lyricsId);
        $this::assertNull($deleted);
    }

    public function testJellyfinLyricsView(): void
    {
        $auth = $this->createAuthorizedUser('jellyview@test.test', 'jellyview');
        $settings = Settings::getSettings($auth->getUser()?->getId() ?? 0);
        $settings->jellyfin_connected = true;
        $settings->jellyfin_server = 'http://jellyfin.test';
        $settings->jellyfin_token = 'token';

        $lyrics = new Lyrics();
        $lyrics->jellyfinShowId = 'show-2';
        $lyrics->jellyfinSeasonNumber = 2;
        $lyrics->jellyfinStartEpisodeNumber = 5;
        $lyrics->jellyfinEndEpisodeNumber = 5;
        $lyrics->lyrics = 'episode lyric';
        DoctrineRegistry::get()->persist($lyrics);
        DoctrineRegistry::get()->flush();

        $_GET = ['show_id' => 'show-2', 'season_index' => 2, 'ep_index' => 5];
        $result = LyricsRoute::jellyfinLyrics();

        $this::assertStringContainsString('jellyfin show-2 1 no', $result);
    }

    public function testSpotifyLyricsBranchesAndEditFlow(): void
    {
        $auth = $this->createAuthorizedUser('spotify@test.test', 'spotify');
        $settings = Settings::getSettings($auth->getUser()?->getId() ?? 0);
        $settings->allow_edit = true;
        $settings->spotify_connected = true;

        $session = new \SpotifyWebAPI\Session();
        $_SESSION['spotify_session'] = $session;
        $route = new LyricsRoute();

        \SpotifyWebAPI\SpotifyWebAPI::$nextPlaybackInfo = null;
        $noSong = $this->captureOutput(fn() => $route->spotifyLyrics());
        $this::assertStringContainsString('No song is currently playing', $noSong);

        \SpotifyWebAPI\SpotifyWebAPI::$nextPlaybackInfo = $this->makePlaybackInfo(null);
        $noItem = $this->captureOutput(fn() => $route->spotifyLyrics());
        $this::assertStringContainsString('No song is currently playing', $noItem);

        $lyrics = new Lyrics();
        $lyrics->spotifyId = 'track-1';
        $lyrics->lyrics = 'existing lyric';
        DoctrineRegistry::get()->persist($lyrics);
        DoctrineRegistry::get()->flush();

        \SpotifyWebAPI\SpotifyWebAPI::$nextPlaybackInfo = $this->makePlaybackInfo($lyrics->spotifyId);
        $fullSong = $this->captureOutput(fn() => $route->spotifyLyrics());
        $this::assertStringContainsString('spotify Track One track-1 existing lyric', $fullSong);

        $_GET = ['id' => 'track-1'];
        \SpotifyWebAPI\SpotifyWebAPI::$nextTrack = (object) [
            'id' => 'track-1',
            'name' => 'Track One',
            'duration_ms' => 180000,
            'artists' => [(object) ['name' => 'Artist']],
            'album' => (object) [
                'images' => [(object) ['url' => 'https://img.test/1']],
            ],
        ];
        $edit = $this->captureOutput(fn() => $route->spotifyLyricsEdit());
        $this::assertStringContainsString('spedit track-1 existing lyric', $edit);

        $_POST = ['id' => 'track-1', 'lyrics' => 'updated lyric'];
        $this->captureOutput(fn() => $route->updateSpotifyLyrics());
        $updated = DoctrineRegistry::get()->getRepository(Lyrics::class)->findOneBy(['spotifyId' => 'track-1']);
        $this::assertSame('updated lyric', $updated?->lyrics);
    }

    private function createAuthorizedUser(string $email, string $username): DefaultAuth
    {
        $password = 'Aa123456';
        $user = new User($email, $username, password_hash($password, PASSWORD_BCRYPT));
        DoctrineRegistry::get()->persist($user);
        DoctrineRegistry::get()->flush();

        $auth = new DefaultAuth();
        $auth->login($email, $password);
        AuthSession::set($auth);
        return $auth;
    }

    private function makePlaybackInfo(?string $trackId): object
    {
        if ($trackId === null) {
            return (object) ['item' => null];
        }

        return (object) [
            'item' => (object) [
                'id' => $trackId,
                'name' => 'Track One',
                'duration_ms' => 180000,
                'artists' => [(object) ['name' => 'Artist']],
                'album' => (object) [
                    'images' => [(object) ['url' => 'https://img.test/1']],
                ],
            ],
            'progress_ms' => 45000,
            'is_playing' => true,
        ];
    }

    /**
     * @template T
     * @param callable():T $callback
     * @return string
     */
    private function captureOutput(callable $callback): string
    {
        ob_start();
        $callback();
        return (string) ob_get_clean();
    }
}
