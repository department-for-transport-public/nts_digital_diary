<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class ChangeUserEmailAddress extends Constraint
{

    public function __construct(
        public string  $message = 'common.email.already-used',
        public ?string $userId = null,
        ?array         $groups = null,
        mixed          $payload = null
    ) {
        parent::__construct([], $groups, $payload);
    }
}
