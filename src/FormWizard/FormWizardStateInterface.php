<?php


namespace App\FormWizard;


interface FormWizardStateInterface
{
    public function getPlace(): ?Place;
    public function setPlace($place, $context = []): self;

    public function getPlaceParameter($key, $defaultValue = null);

    public function getSubject();
    public function setSubject($subject): self;

    public function isValidHistoryPlace(Place $place): bool;
    public function addPlaceToHistory(Place $place);
    public function getPreviousHistoryPlace(): ?Place;
    public function getPreviousHistoryPlaceRouteParameters(): array;
}