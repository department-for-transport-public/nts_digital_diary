<?php


namespace App\FormWizard;

abstract class AbstractFormWizardState implements FormWizardStateInterface
{
    /**
     * @var string | Place
     */
    protected $place = null;
    /** @var array | Place[] */
    protected array $placeHistory = [];

    /**
     * @return Place|string|null
     */
    public function getPlace(): ?Place
    {
        return $this->place;
    }

    public function getPlaceParameter($key, $defaultValue = null)
    {
        if ($this->place instanceof Place) {
            return $this->place->context[$key] ?? $defaultValue;
        }
        return $defaultValue;
    }

    public function addPlaceToHistory(Place $place): self
    {
        $this->placeHistory[] = $place;
        return $this;
    }

    public function setPlace($place, $context = []): self
    {
        $place = $this->getHistoryOrNewPlace($place, $context);

        if (in_array($place, $this->placeHistory)) {
            // split the history on the index (lose the remainder of the states)
            $this->placeHistory = array_slice($this->placeHistory, 0, array_search($place, $this->placeHistory));
        }

        $this->place = $place;
        return $this;
    }

    protected function getHistoryOrNewPlace($place, $context): Place
    {
        $newPlace = $place instanceof Place ? $place : new Place($place, $context);
        foreach ($this->placeHistory as $historyPlace)
        {
            if ($historyPlace->isSameAs($newPlace)) {
                return $historyPlace;
            }
        }
        return $newPlace;
    }

    public function isValidHistoryPlace(Place $place): bool
    {
        return in_array($place, $this->placeHistory);
    }

    public function getPreviousHistoryPlace(): ?Place
    {
        if (empty($this->placeHistory)) return null;
        return $this->placeHistory[array_key_last($this->placeHistory)];
    }

    public function getPreviousHistoryPlaceRouteParameters(): array
    {
        return [
            'place' => $this->getPreviousHistoryPlace(),
        ];
    }
}