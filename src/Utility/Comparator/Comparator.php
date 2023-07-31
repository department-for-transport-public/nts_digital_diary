<?php

namespace App\Utility\Comparator;

use App\Utility\Comparator\Exception\ComparatorException;
use App\Utility\Comparator\Exception\MisconfigurationException;
use App\Utility\Comparator\Exception\UnsupportedComparisonException;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

class Comparator
{
    public function __construct(
        #[TaggedLocator('app.comparator')]
        protected ServiceLocator $locator
    ) {}

    /**
     * @throws ComparatorException
     */
    public function areEqual($a, $b, array $options = []): bool
    {
        foreach(array_keys($this->locator->getProvidedServices()) as $name) {
            try {
                $service = $this->locator->get($name);
            } catch (ContainerExceptionInterface) {
                continue;
            }

            if (!$service instanceof ComparatorInterface) {
                throw new MisconfigurationException("Service $name does not implement \ComparatorInterface ".get_class($service));
            }

            if ($service::canCompareEquality($a, $b)) {
                return $service::areEqual($a, $b, $options);
            }
        }

        throw new UnsupportedComparisonException('Comparator does not know how to compare these arguments');
    }
}