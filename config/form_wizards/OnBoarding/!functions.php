<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator {
    function ob_place(
        string $name,
        ?string $formClass = null,
        ?string $translationPrefix = null,
        ?string $template = null,
        array $additionalMetadata = []
    ): array {
        return [
            'name' => $name,
            'metadata' => array_merge_recursive([
                    'template' => $template ?? "on_boarding/base_form.html.twig",
                    'view_data' => $translationPrefix ? [
                        'translation_prefix' => $translationPrefix,
                    ] : [],
                ],
                $formClass ? ['form_class' => $formClass] : [],
                $additionalMetadata
            )
        ];
    }

    function ob_transition(string $name, string $from, string $to, ?string $guard = null, array $additionalMetadata = []): array {
        return array_merge_recursive(
            [
                'name' => $name,
                'from' => $from,
                'to' => $to,
            ],
            $guard ? ['guard' => $guard] : [],
            ['metadata' => $additionalMetadata]
        );
    }

}
