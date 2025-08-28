<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class PasswordComplexity extends Constraint
{
    public function __construct(
        public string $message = 'common.password.not-complex-enough',
        ?array        $groups = null,
        mixed         $payload = null
    ){
        parent::__construct([], $groups, $payload);
    }
}
