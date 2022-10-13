<?php

namespace App\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

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