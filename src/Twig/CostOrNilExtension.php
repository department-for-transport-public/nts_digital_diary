<?php

namespace App\Twig;

use App\Entity\Embeddable\CostOrNil;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CostOrNilExtension extends AbstractExtension
{
    public function __construct(protected TranslatorInterface $translator)
    {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('format_cost_or_nil', $this->format_cost_or_nil(...)),
        ];
    }

    public function format_cost_or_nil(?CostOrNil $costOrNil, string $translationKey, string $emptyPlaceholder = ' - ', string $nilTranslationKey = 'common.cost.nil'): string
    {
        if ($costOrNil && $costOrNil->getHasCost() === false) {
            return $this->translator->trans($nilTranslationKey, [], 'travel-diary');
        }

        $cost = $costOrNil?->getCost();

        if ($cost === null || $cost->isZero()) {
            return $emptyPlaceholder;
        }

        return $this->translator->trans($translationKey, [
            'cost' => $cost->toFloat(),
        ], 'travel-diary');
    }
}