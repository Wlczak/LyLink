<?php

namespace Lylink\Models;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'jellyfin_users')]

class JellyfinUser
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    // @phpstan-ignore property.unusedType
    private int|null $id = null;

    // #[ORM\Column(type: 'string')]
    // public string $username;

    #[ORM\Column(type: 'string')]
    public string $jellyfinAddress;

    #[ORM\Column(type: 'string')]
    public string $jellyfinToken;

    public function getId(): int | null
    {
        return $this->id;
    }

    public function __construct( string $jellyfinAddress, string $jellyfinToken)
    {
        $this->jellyfinAddress = $jellyfinAddress;
        $this->jellyfinToken = $jellyfinToken;
    }
}
