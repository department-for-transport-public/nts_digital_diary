<?php

namespace App\ExpressionLanguage;

use App\Attribute\AutoconfigureTag\ExpressionLanguageProvider;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

#[ExpressionLanguageProvider(ExpressionLanguageProvider::SECURITY)]
#[ExpressionLanguageProvider(ExpressionLanguageProvider::ROUTER)]
#[ExpressionLanguageProvider(ExpressionLanguageProvider::VALIDATOR)]
#[ExpressionLanguageProvider(ExpressionLanguageProvider::WORKFLOW)]
class GeneralFunctionProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions(): array
    {
        return [
            new ExpressionFunction('isEmpty',
                fn($str): string => "isEmpty($str)",
                fn($arguments, $str): bool => empty($str)
            ),
            new ExpressionFunction('isNull',
                fn($str): string => "isNull($str)",
                fn($arguments, $str): bool => is_null($str)
            ),
        ];
    }
}