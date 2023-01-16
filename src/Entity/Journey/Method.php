<?php

namespace App\Entity\Journey;

use App\Repository\Journey\MethodRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=MethodRepository::class)
 */
class Method implements \JsonSerializable
{
    public const TYPE_PRIVATE = 'private';
    public const TYPE_PUBLIC = 'public';
    public const TYPE_OTHER = 'other';

    public static function method(int $id, ?int $code, string $descriptionTranslationKey, string $type, string $displayGroup, ?int $sort = null): Method
    {
        return (new Method())
            ->setId($id)
            ->setCode($code)
            ->setDescriptionTranslationKey($descriptionTranslationKey)
            ->setType($type)
            ->setDisplayGroup($displayGroup)
            ->setSort($sort ?? $id);
    }

    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     */
    private ?int $id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $code;

    /**
     * @ORM\Column(type="string", length=30)
     */
    private ?string $descriptionTranslationKey;

    /**
     * @ORM\Column(type="string", length=10)
     * @Assert\Choice(choices={"other","private","public"})
     */
    private ?string $type;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private ?string $displayGroup = null;

    /**
     * @ORM\Column(type="integer")
     */
    private ?int $sort;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getDescriptionTranslationKey(): ?string
    {
        return $this->descriptionTranslationKey;
    }

    public function setDescriptionTranslationKey(string $descriptionTranslationKey): self
    {
        $this->descriptionTranslationKey = $descriptionTranslationKey;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getCode(): ?int
    {
        return $this->code;
    }

    public function setCode(?int $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getDisplayGroup(): ?string
    {
        return $this->displayGroup;
    }

    public function setDisplayGroup(string $displayGroup): self
    {
        $this->displayGroup = $displayGroup;

        return $this;
    }

    public function getSort(): ?int
    {
        return $this->sort;
    }

    public function setSort(int $sort): self
    {
        $this->sort = $sort;

        return $this;
    }

    // -----

    public function jsonSerialize(): ?int
    {
        return $this->code;
    }
}
