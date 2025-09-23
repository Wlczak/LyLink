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

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: "settings")]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private User $user;

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

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public static function getSettings(User $user): Settings
    {
        $em = DoctrineRegistry::get();

        $settings = $em->getRepository(Settings::class)->findOneBy(['user' => $user->getId()]);
        if ($settings == null) {
            return new Settings($user);
        }
        return $settings;
    }
}
