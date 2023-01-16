<?php

namespace App\Entity;

use App\Repository\PropertyChangeLogRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PropertyChangeLogRepository::class)
 * @ORM\Table(
 *     indexes={
 *         @ORM\Index(
 *             name="property_change_idx",
 *             fields={"entityId", "entityClass"}
 *         )
 *     })
 */
class PropertyChangeLog
{
    use IdTrait;

    /**
     * @ORM\Column(type="string", length=26)
     */
    private ?string $entityId;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $entityClass;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $propertyName;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $propertyValue;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $interviewerSerialId;

    /**
     * @ORM\Column(type="datetimemicrosecond")
     */
    private ?\DateTime $timestamp;

    public function getEntityId(): ?string
    {
        return $this->entityId;
    }

    public function setEntityId(string $entityId): self
    {
        $this->entityId = $entityId;
        return $this;
    }

    public function getEntityClass(): ?string
    {
        return $this->entityClass;
    }

    public function setEntityClass(string $entityClass): self
    {
        $this->entityClass = $entityClass;
        return $this;
    }

    public function getPropertyName(): ?string
    {
        return $this->propertyName;
    }

    public function setPropertyName(string $propertyName): self
    {
        $this->propertyName = $propertyName;
        return $this;
    }

    public function getPropertyValue()
    {
        return $this->propertyValue;
    }

    public function setPropertyValue($propertyValue): self
    {
        if (is_array($propertyValue) || (is_object($propertyValue) && !$propertyValue instanceof \JsonSerializable)) {
            $propertyName = $this->propertyName ?? '<null>';
            $entityId = $this->entityId ?? '<null>';
            $entityClass = $this->entityClass ?? '<null>';

            throw new \RuntimeException("Non-primitive / non-json-serializable value emitted from ChangeSetNormalizer (class: {$entityClass}, id: {$entityId}, property: {$propertyName}");
        }

        $this->propertyValue = $propertyValue;
        return $this;
    }

    public function getInterviewerSerialId(): ?string
    {
        return $this->interviewerSerialId;
    }

    public function setInterviewerSerialId(?string $interviewerSerialId): self
    {
        $this->interviewerSerialId = $interviewerSerialId;
        return $this;
    }

    public function getTimestamp(): ?\DateTime
    {
        return $this->timestamp;
    }

    public function setTimestamp($timestamp): self
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    public function getIsInterviewer(): bool
    {
        return !is_null($this->interviewerSerialId);
    }
}
