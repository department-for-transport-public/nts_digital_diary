<?php

namespace App\Utility\Metrics\Events;

interface EventInterface
{
    public function getName(): string;
    public function getMetadata(): ?array;
    public function getDiarySerial(): ?string;
}