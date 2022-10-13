<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator {

    function td_place(string $name, ?string $formClass = null, ?string $translationPrefixPart = null, array $additionalMetadata = [], string $template = null): array {
        if ($template === null) {
            if (stripos($formClass, '\\JourneyWizard\\') !== false) {
                $template = 'travel_diary/journey/wizard_form.html.twig';
            } else if (stripos($formClass, '\\StageWizard\\') !== false) {
                $template = 'travel_diary/stage/wizard_form.html.twig';
            } else {
                $template = 'travel_diary/base_form.html.twig';
            }
        }

        return [
            'name' => $name,
            'metadata' => array_merge_recursive([
                    'template' => $template,
                    'view_data' => $translationPrefixPart ? [
                        'translation_prefix' => $translationPrefixPart,
                    ] : [],
                ],
                $formClass ? ['form_class' => $formClass] : [],
                $additionalMetadata
            )
        ];
    }

    function td_transition(string $name, string $from, string $to, ?string $guard = null, array $metadata = []): array {
        return array_merge_recursive(
            [
                'name' => $name,
                'from' => $from,
                'to' => $to,
            ],
            $guard ? ['guard' => $guard] : [],
            ['metadata' => $metadata]
        );
    }

}
