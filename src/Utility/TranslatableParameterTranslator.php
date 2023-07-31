<?php

namespace App\Utility;

use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslatableParameterTranslator implements TranslatorInterface, TranslatorBagInterface, LocaleAwareInterface
{
    protected TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function trans(?string $id, array $parameters = [], string $domain = null, string $locale = null): string
    {
        foreach ($parameters as $key => $param) {
            if ($param instanceof TranslatableInterface) {
                $parameters[$key] = $param->trans($this->translator, $locale);
            }
        }
        return $this->translator->trans($id, $parameters, $domain, $locale);
    }

    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }

    public function setLocale(string $locale)
    {
        $this->translator->setLocale($locale);
    }

    public function getCatalogue(string $locale = null): MessageCatalogueInterface
    {
        return $this->translator->getCatalogue($locale);
    }

    /**
     * Passes through all unknown calls onto the translator object.
     */
    public function __call(string $method, array $args)
    {
        return $this->translator->{$method}(...$args);
    }
}