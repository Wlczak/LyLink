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
        private string $clientId = '',
        private string $clientSecret = '',
        private string $redirectUri = ''
    ) {
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
    public static array $nextMe = ['display_name' => 'Test User'];
    public static ?string $lastAccessToken = null;

    public function setAccessToken(string $token): void
    {
        self::$lastAccessToken = $token;
    }

    public function me(): object|array
    {
        return (object) self::$nextMe;
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
