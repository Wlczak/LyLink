<?php

namespace Lylink\Models;

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

    #[ORM\Column(name: 'spotify_id', type: 'string', nullable: true)]
    public string $spotifyId = "";

    #[ORM\Column(type: 'string', name: 'lyrics', nullable: true)]
    public string $lyrics = '';

    #[ORM\Column(type: 'string', name: 'jellyfin_show_id', nullable: true)]
    public string $jellyfinShowId = '';

    #[ORM\Column(type: 'string', name: 'jellyfin_lyrics_name', nullable: true)]
    public string $jellyfinLyricsName = "";

    #[ORM\Column(type: 'integer', name: 'jellyfin_season_number', nullable: true)]
    public int $jellyfinSeasonNumber = 0;

    #[ORM\Column(type: 'integer', name: 'jellyfin_start_episode_number', nullable: true)]
    public int $jellyfinStartEpisodeNumber = 0;

    #[ORM\Column(type: 'integer', name: 'jellyfin_end_episode_number',nullable: true)]
    public int $jellyfinEndEpisodeNumber = 0;

    /**
     * @return int|null
     */
    public function getId(): int | null
    {
        return $this->id;
    }
}
