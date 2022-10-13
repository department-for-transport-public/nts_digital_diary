<?php

namespace App\ExpressionLanguage;

use App\Features;
use Exception;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\ExpressionLanguage\SyntaxError;

class FeatureFunctionProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions(): array
    {
        return [
            new ExpressionFunction('is_feature_enabled',
                function($str) {
                    return "is_feature_enabled($str)";
                },
                function($arguments, $str) {
                    try {
                        return Features::isEnabled($str);
                    } catch (Exception $e) {
                        throw new SyntaxError("Unknown feature '$str'");
                    }
                }
            ),
        ];
    }
}