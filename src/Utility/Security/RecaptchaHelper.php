<?php

namespace App\Utility\Security;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class RecaptchaHelper extends AbstractExtension
{
    protected bool $recaptchaUsed;

    public function __construct(
        protected ?string $recaptchaSecretKey,
        protected ?string $recaptchaSiteKey,
    ) {
        $this->recaptchaUsed = false;
    }

    public function isRecaptchaUsed(): bool
    {
        return $this->recaptchaUsed;
    }

    public function setRecaptchaUsed(bool $recaptchaUsed): self
    {
        if ($this->recaptchaSecretKey && $this->recaptchaSiteKey) {
            $this->recaptchaUsed = $recaptchaUsed;
        }

        return $this;
    }

    public function getRecaptchaSecretKey(): ?string
    {
        return $this->recaptchaSecretKey;
    }

    public function getRecaptchaSiteKey(): ?string
    {
        return $this->recaptchaSiteKey;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('isRecaptchaUsed', fn() => $this->recaptchaUsed),
        ];
    }
}