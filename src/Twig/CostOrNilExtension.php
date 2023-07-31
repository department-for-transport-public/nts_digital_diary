<?php

namespace App\Twig;

use App\Entity\CostOrNil;
use Brick\Math\BigDecimal;
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
            new TwigFilter('format_cost_or_nil', [$this, 'format_cost_or_nil']),
        ];
    }

    public function format_cost_or_nil(?CostOrNil $costOrNil, string $translationKey, string $emptyPlaceholder = ' - ', string $nilTranslationKey = 'common.cost.nil'): string
    {
        if ($costOrNil->getHasCost() === false) {
            return $this->translator->trans($nilTranslationKey, [], 'travel-diary');
        }

        return $this->format_string($costOrNil?->getCost(), $translationKey, $emptyPlaceholder);
    }

    private function format_string(?string $decimal, string $translationKey, string $emptyPlaceHolder = ' - '): string
    {
        if ($decimal === null) {
            return $emptyPlaceHolder;
        }

        return $this->format_big_decimal(BigDecimal::of($decimal), $translationKey, $emptyPlaceHolder);
    }

    private function format_big_decimal(?BigDecimal $decimal, string $translationKey, string $emptyPlaceHolder = ' - '): string
    {
        if ($decimal === null || $decimal->isZero()) {
            return $emptyPlaceHolder;
        }

        return $this->translator->trans($translationKey, [
            'cost' => $decimal->toFloat(),
        ], 'travel-diary');
    }
}