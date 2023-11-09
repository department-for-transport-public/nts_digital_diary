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
                function($str) {return "isEmpty($str)";},
                function($arguments, $str) {return empty($str);}
            ),
            new ExpressionFunction('isNull',
                function($str) {return "isNull($str)";},
                function($arguments, $str) {return is_null($str);}
            ),
        ];
    }
}