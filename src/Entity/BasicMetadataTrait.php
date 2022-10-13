<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

trait BasicMetadataTrait
{
    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected \DateTime $createdAt;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected string $createdBy;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected \DateTime $modifiedAt;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected string $modifiedBy;

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getCreatedBy(): string
    {
        return $this->createdBy;
    }

    public function setCreatedBy(string $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getModifiedAt(): \DateTime
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(\DateTime $modifiedAt): self
    {
        $this->modifiedAt = $modifiedAt;
        return $this;
    }

    public function getModifiedBy(): string
    {
        return $this->modifiedBy;
    }

    public function setModifiedBy(string $modifiedBy): self
    {
        $this->modifiedBy = $modifiedBy;
        return $this;
    }
}