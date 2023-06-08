<?php

namespace App\Form;

use App\Utility\RecaptchaHelper;
use App\Validator\Constraints\Recaptcha;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecaptchaType extends AbstractType
{
    public function __construct(protected RecaptchaHelper $recaptchaHelper)
    {
        $this->recaptchaHelper->setRecaptchaUsed(true);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'label' => false,
                'compound' => false,
                'constraints' => [
                    new Recaptcha(),
                ],
            ])
            ->setNormalizer('attr', function(OptionsResolver $options, $value) {
                if (!is_array($value)) {
                    $value = [];
                }

                // Add g-recaptcha to classes
                $classes = array_filter(
                    array_map('trim', explode(' ', $value['class'] ?? '')),
                    fn($x) => $x !== ''
                );

                if (!in_array('g-recaptcha', $classes)) {
                    array_unshift($classes, 'g-recaptcha');
                }

                $value['class'] = join(' ', $classes);

                // Add required data attributes
                $value['data-sitekey'] = $this->recaptchaHelper->getRecaptchaSiteKey();

                return $value;
            });
    }
}