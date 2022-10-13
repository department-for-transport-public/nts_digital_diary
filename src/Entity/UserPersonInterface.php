<?php


namespace App\Entity;


interface UserPersonInterface
{
    public function getId(): ?string;
    public function getName(): ?string;
    public function setName(?string $name): self;
    public function getUser(): ?User;
    public function setUser(?User $user): self;
}