<?php

namespace App\Entity;

interface BasicMetadataInterface
{
    public function getCreatedAt(): \DateTime;
    public function setCreatedAt(\DateTime $createdAt): self;
    public function getCreatedBy(): string;
    public function setCreatedBy(string $createdBy): self;
    public function getModifiedAt(): \DateTime;
    public function setModifiedAt(\DateTime $modifiedAt): self;
    public function getModifiedBy(): string;
    public function setModifiedBy(string $modifiedBy): self;
}