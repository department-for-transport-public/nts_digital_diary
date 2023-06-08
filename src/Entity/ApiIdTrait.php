<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;

trait ApiIdTrait
{
    use IdTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="string", unique=true, length=26)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UlidGenerator::class)
     */
    #[ApiProperty(identifier: false)]
    protected ?string $id;
}