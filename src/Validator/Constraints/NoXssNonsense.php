<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class NoXssNonsense extends Constraint
{
    public function __construct(
        public string $messageNoGtOrLt = 'feedback.form.xss-nonsense.no-gt-or-lt',
        public string $messageNoBadStrings = 'feedback.form.xss-nonsense.no-bad-strings',
        ?array        $groups = null,
        mixed         $payload = null
    ) {
        parent::__construct([], $groups, $payload);
    }
}
