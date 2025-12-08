<?php

namespace Lylink\Models;

use Doctrine\ORM\Mapping as ORM;
use Lylink\DoctrineRegistry;

#[ORM\Entity]
#[ORM\Table(name: 'settings')]
class Settings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    // @phpstan-ignore property.unusedType
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private int $user_id;

    // #[ORM\Column(type: 'string', length: 255, nullable: true)]
    // private ?string $api_token = null;

    // #[ORM\Column(type: 'boolean')]
    // private bool $auto_follow = false;

    // #[ORM\Column(type: 'boolean')]
    // private bool $highlight_current = false;

    // #[ORM\Column(type: 'boolean')]
    // private bool $sync_on_open = false;

    ## Spotify ##
    #[ORM\Column(type: 'boolean')]
    public bool $allow_spotify_login = false;

    #[ORM\Column(type: 'boolean')]
    public bool $spotify_connected = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    public ?string $spotify_user_id = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    public ?string $spotify_user_display_name = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    public ?string $spotify_token = null;

    ## Jellyfin ##
    #[ORM\Column(type: 'boolean')]
    public bool $jellyfin_connected = false;

    #[ORM\Column(type: 'boolean')]
    public bool $allow_jellyfin_login = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    public ?string $jellyfin_server = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    public ?string $jellyfin_user_id = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    public ?string $jellyfin_token = null;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    public bool $allow_edit = false;

    public function __construct(int $userId)
    {
        $this->user_id = $userId;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public static function getSettings(int $userId): Settings
    {
        $em = DoctrineRegistry::get();

        $settings = $em->getRepository(Settings::class)->findOneBy(["user_id" => $userId]);
        if ($settings === null) {
            $settings = new Settings($userId);
            $em->persist($settings);
            $em->flush();
        }
        return $settings;
    }

    public function disconnectJellyfin(): void
    {
        $this->allow_jellyfin_login = false;
        $this->jellyfin_connected = false;
        $this->jellyfin_server = null;
        $this->jellyfin_user_id = null;
        $this->jellyfin_token = null;
        $em = DoctrineRegistry::get();
        $em->persist($this);
        $em->flush();
    }

    public function connectSpotify(string $token, string $username): void
    {
        $this->spotify_connected = true;
        $this->spotify_token = $token;
        $this->spotify_user_display_name = $username;
        $em = DoctrineRegistry::get();
        $em->persist($this);
        $em->flush();
    }

    public function disconnectSpotify(): void
    {
        $this->allow_spotify_login = false;
        $this->spotify_connected = false;
        $this->spotify_user_id = null;
        $this->spotify_user_display_name = null;
        $this->spotify_token = null;
        $em = DoctrineRegistry::get();
        $em->persist($this);
        $em->flush();
    }
}
