<?php

namespace App\Entity;

use App\FormWizard\PropertyMergerNonEntityInterface;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * @ORM\Embeddable()
 */
class CostOrNil implements JsonSerializable, PropertyMergerNonEntityInterface
{
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $cost;

    /**
     * Tom Sykes (2023-05-16): I investigated persisting this value to be able to remove logic from setters, and the
     * need for the data mapper on the form, however, since the Export API outputs a single property, and (because of
     * that) the change log normalizer also represents this cost in a single property, it makes more sense to store this
     * as a single property
     */
    private ?bool $hasCost;

    public function getCost(): ?int
    {
        return $this->cost ?? null;
    }

    public function getHasCost():?bool
    {
        return $this->hasCost ?? match($this->cost) {
            null => null,
            0 => false,
            default => true
        };
    }

    public function setCost(?int $cost): self
    {
        $this->cost = $cost;
        return $this;
    }

    public function setHasCost(?bool $hasCost): self
    {
        $this->hasCost = $hasCost;
        return $this;
    }

    public function jsonSerialize(): ?int
    {
        return $this->cost;
    }

    public static function getMergeProperties(): array
    {
        return [
            'hasCost',
            'cost',
        ];
    }

}