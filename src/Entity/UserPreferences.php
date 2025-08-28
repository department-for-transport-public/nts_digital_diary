<?php

namespace App\Entity;

use App\Utility\DateTimeFormats;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserPreferencesRepository;

#[ORM\Entity(repositoryClass: UserPreferencesRepository::class)]
class UserPreferences
{
    use IdTrait;

    #[ORM\OneToOne(mappedBy: 'preferences', targetEntity: User::class)]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 12, nullable: false)]
    private ?string $timeFormat = DateTimeFormats::MODE_12_HOUR;

    public function getTimeFormat(): ?string
    {
        return $this->timeFormat;
    }

    public function setTimeFormat(?string $timeFormat): static
    {
        $this->timeFormat = $timeFormat;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }
}