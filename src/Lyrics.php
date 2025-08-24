<?php

namespace Lylink;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'lyrics')]

class Lyrics
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    // @phpstan-ignore property.unusedType
    private int|null $id = null;

    #[ORM\Column(type: 'string', unique: true)]    
    public string $spotify_id;

    #[ORM\Column(type: 'string')]
    public string $lyrics;

    /**
     * @return int|null
     */
    public function getId(): int | null
    {
        return $this->id;
    }
}
