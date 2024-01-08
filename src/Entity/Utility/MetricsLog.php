<?php

namespace App\Entity\Utility;

use App\Entity\IdTrait;
use App\Repository\Utility\MetricsLogRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=MetricsLogRepository::class)
 */
class MetricsLog
{
    use IdTrait;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private \DateTimeImmutable $createdAt;

    /**
     * @ORM\Column(type="string", length=180, nullable=true)
     */
    private ?string $userSerial;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private ?string $diarySerial;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $event;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private ?array $metadata = [];

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUserSerial(): ?string
    {
        return $this->userSerial;
    }

    public function setUserSerial(string $userSerial): self
    {
        $this->userSerial = $userSerial;

        return $this;
    }

    public function getDiarySerial(): ?string
    {
        return $this->diarySerial;
    }

    public function setDiarySerial(?string $diarySerial): self
    {
        $this->diarySerial = $diarySerial;

        return $this;
    }

    public function getEvent(): ?string
    {
        return $this->event;
    }

    public function setEvent(string $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }
}
