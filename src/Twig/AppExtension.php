<?php

namespace App\Twig;

use App\Features;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Error\SyntaxError;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack) {
        $this->requestStack = $requestStack;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('flattenChoices', [$this, 'flattenChoices']),
            new TwigFilter('data_uri', [$this, 'dataUri']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('choiceLabel', [$this, 'choiceLabel']),
            new TwigFunction('is_feature_enabled', [$this, 'isFeatureEnabled']),
            new TwigFunction('shiftMapping', [$this, 'shiftMapping']),
        ];
    }

    /**
     * @throws SyntaxError
     */
    public function isFeatureEnabled($str): bool {
        try {
            return Features::isEnabled($str);
        } catch(Exception $e) {
            throw new SyntaxError("Unknown feature '$str'");
        }
    }

    public function choiceLabel(array $choices, ?string $choice, bool $equivalence=false): string {
        foreach($choices as $label => $value) {
            if ($value === $choice || ($equivalence && $value == $choice)) {
                return $label;
            }
        }

        return '';
    }

    public function flattenChoices(array $choices): array {
        $output = [];
        foreach($choices as $label => $choice) {
            if (is_array($choice)) {
                $output = array_merge($output, $this->flattenChoices($choice));
            } else {
                $output[$label] = $choice;
            }
        }

        return $output;
    }

    public function getGlobals(): array
    {
        return [
            'translation_domain' => $this->getAutoTranslationDomain(),
        ];
    }

    protected function getAutoTranslationDomain(): ?string
    {
        $routeTranslationMap = [
            'traveldiary' => 'travel-diary',
            'onboarding' => 'on-boarding',
            'interviewer' => 'interviewer',
        ];

        if ($this->requestStack->getCurrentRequest()) {
            $route = $this->requestStack->getCurrentRequest()->attributes->get('_route', "");
            if (preg_match('/^(?<prefix>[^_]+)/', $route, $matches)) {
                return $routeTranslationMap[$matches['prefix']] ?? null;
            }
        }
        return null;
    }

    public function shiftMapping(array $mapping, int $key, string $direction): array {
        if (!in_array($direction, ['up', 'down'])) {
            throw new \RuntimeException('Direction must be "up" or "down"');
        }

        if ($key < 0 || $key >= count($mapping)) {
            throw new \RuntimeException('Key out of bounds');
        }

        if (($key === 0 && $direction === 'up') || ($key === count($mapping) - 1 && $direction === 'down')) {
            return $mapping;
        }

        $temp = $mapping[$key];

        if ($direction === 'up') {
            $mapping[$key] = $mapping[$key - 1];
            $mapping[$key - 1] = $temp;
        } else {
            $mapping[$key] = $mapping[$key + 1];
            $mapping[$key + 1] = $temp;
        }

        return $mapping;
    }

    public function dataUri(string $data, string $mimeType, array $parameters = []): string
    {
        $dataUri = 'data:' . $mimeType;

        foreach ($parameters as $key => $value) {
            $dataUri .= ';'.$key.'='.rawurlencode($value);
        }

        if (0 === strpos($mimeType, 'text/')) {
            $dataUri .= ','.rawurlencode($data);
        } else {
            $dataUri .= ';base64,'.base64_encode($data);
        }

        return $dataUri;
    }
}