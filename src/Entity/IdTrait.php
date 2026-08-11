<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Component\Uid\Ulid;

trait IdTrait
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    protected ?string $id;

    public function getId(): ?string
    {
        return $this->id ?? null;
    }

    public function getIdCreatedAt(): ?\DateTimeImmutable
    {
        return Ulid::fromString($this->id)->getDateTime();
    }
}