<?php

namespace SpotifyWebAPI;

class SpotifyWebAPIException extends \Exception
{
}

class Session
{
    public static ?string $nextAuthorizeUrl = null;
    public static bool $nextRequestAccessTokenResult = true;
    public static ?string $nextAccessToken = 'token';
    public static int $nextTokenExpiration = 9999999999;

    public function __construct(
        string $clientId = '',
        string $clientSecret = '',
        string $redirectUri = ''
    ) {
        $unused = [$clientId, $clientSecret, $redirectUri];
    }

    public function refreshAccessToken(): void
    {
    }

    public function getAccessToken(): string
    {
        return self::$nextAccessToken ?? '';
    }

    public function getTokenExpiration(): int
    {
        return self::$nextTokenExpiration;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function getAuthorizeUrl(array $options = []): string
    {
        return self::$nextAuthorizeUrl ?? 'https://example.test/auth';
    }

    public function requestAccessToken(string $code): bool
    {
        return self::$nextRequestAccessTokenResult;
    }
}

class SpotifyWebAPI
{
    public static mixed $nextPlaybackInfo = null;
    public static mixed $nextTrack = null;
    /** @var array{display_name:string} */
    public static array $nextMe = ['display_name' => 'Test User'];
    public static ?string $lastAccessToken = null;

    public function setAccessToken(string $token): void
    {
        self::$lastAccessToken = $token;
    }

    /**
     * @return array{display_name:string}
     */
    public function me(): array
    {
        return self::$nextMe;
    }

    public function getMyCurrentPlaybackInfo(): mixed
    {
        if (self::$nextPlaybackInfo instanceof SpotifyWebAPIException) {
            throw self::$nextPlaybackInfo;
        }

        return self::$nextPlaybackInfo;
    }

    public function getTrack(string $trackId): mixed
    {
        return self::$nextTrack;
    }
}
