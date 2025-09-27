<?php

namespace Lylink\Models;

use Doctrine\ORM\Mapping as ORM;
use Exception;
use Lylink\DoctrineRegistry;
use SensitiveParameter;

#[ORM\Entity]
#[ORM\Table(name: 'users')]

# Id | username | email | pasword | spotify id | allow spotify login | jellyfin account id | allow jellyfin login |||

class User
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    // @phpstan-ignore property.unusedType
    private int|null $id = null;

    #[ORM\Column(type: 'string')]
    public string $username;

    #[ORM\Column(type: 'string')]
    private string $email;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private bool $emailVerified = false;

    #[ORM\Column(type: 'string')]
    private string $password;

    /**
     * @return int|null
     */
    public function getId(): int | null
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerified;
    }

    public function checkPassword(#[SensitiveParameter] string $password): bool
    {
        return password_verify($password, $this->password);
    }

    public function __construct(string $email, string $username, #[SensitiveParameter] string $password)
    {
        $this->email = $email;
        $this->username = $username;
        $this->password = $password;
    }

    public function __toString(): string
    {
        return $this->username;
    }

    public function updateJellyfin(string $address, #[SensitiveParameter] string $token, bool $allow): void
    {
        $em = DoctrineRegistry::get();

        $id = $this->id;

        if ($id === null) {
            throw new Exception("this user is not in db");
        }

        $settings = Settings::getSettings($id);

        if ($settings == null) {
            $settings = new Settings($id);
        }
        $settings->jellyfin_server = $address;
        $settings->jellyfin_token = $token;
        $settings->jellyfin_user_id = null;
        $settings->allow_jellyfin_login = $allow;
        $settings->jellyfin_connected = true;

        $em->persist($settings);
        $em->flush();
    }

    public function verifyEmail(): void
    {
        $this->emailVerified = true;
        $em = DoctrineRegistry::get();
        $em->persist($this);
        $em->flush();
    }

}
