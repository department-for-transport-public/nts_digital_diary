<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator {
    function satis_place(string $name, string $template, ?string $formClass = null, ?string $translationPrefixPart = null, array $additionalMetadata = []): array {
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

    function satis_transition(string $name, string $from, string $to, ?string $guard = null, array $metadata = []): array {
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
