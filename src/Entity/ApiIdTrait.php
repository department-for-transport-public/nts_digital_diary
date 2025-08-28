<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;

trait ApiIdTrait
{
    use IdTrait;

    #[ApiProperty(identifier: false)]
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    protected ?string $id;
}