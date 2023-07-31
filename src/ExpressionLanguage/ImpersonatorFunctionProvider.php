<?php

namespace App\ExpressionLanguage;

use App\Security\ImpersonatorAuthorizationChecker;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class ImpersonatorFunctionProvider implements ExpressionFunctionProviderInterface
{
    public function __construct(protected ImpersonatorAuthorizationChecker $impersonatorAuthorizationChecker)
    {}

    public function getFunctions(): array
    {
        return [
            // Can't do this quite the same as is_granted (in framework-extra-bundle/src/Security/ExpressionLanguage.php),
            // as SecurityListener.php makes it very difficult to extend getVariables to add impersonator_auth_checker
            // as a variable.
            //
            // Instead, we just provide the function directly.
            new ExpressionFunction('impersonator_is_granted',
                function($attributes, $object='null') {
                    return sprintf('impersonator_is_granted(%s, %s)', $attributes, $object);
                },
                function(array $variables, $attributes, $object=null) {
                    return $this->impersonatorAuthorizationChecker->isGranted($attributes, $object);
                }
            ),
        ];
    }
}