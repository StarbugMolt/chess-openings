<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'user_account')]
#[ORM\UniqueConstraint(name: 'uniq_account_email', columns: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    // @phpstan-ignore property.unusedType (assigned by Doctrine hydration)
    private ?int $id = null;

    /** @var non-empty-string */
    #[ORM\Column(length: 254)]
    private string $email;

    #[ORM\Column(length: 255)]
    private string $password = '';

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'verified_at', type: 'datetime_immutable', nullable: true)]
    // @phpstan-ignore property.unusedType (assigned by Doctrine hydration)
    private ?\DateTimeImmutable $verifiedAt = null;

    public function __construct(string $email)
    {
        if ('' === $email) {
            throw new \InvalidArgumentException('Email must not be empty.');
        }
        $this->email = $email;
        $this->createdAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(#[\SensitiveParameter] string $password): void
    {
        $this->password = $password;
    }

    public function isVerified(): bool
    {
        return null !== $this->verifiedAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
    }

    /** Symfony recognises this checksum when refreshing a session after password changes. */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    /** @return array{id: string, email: string, verified: bool, createdAt: string} */
    public function toArray(): array
    {
        return ['id' => (string) $this->id, 'email' => $this->email, 'verified' => $this->isVerified(), 'createdAt' => $this->createdAt->format(DATE_ATOM)];
    }
}
