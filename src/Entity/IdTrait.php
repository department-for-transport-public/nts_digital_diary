<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;

trait IdTrait
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string", unique=true, length=26)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UlidGenerator::class)
     */
    protected ?string $id;

    public function getId(): ?string
    {
        return $this->id ?? null;
    }
}