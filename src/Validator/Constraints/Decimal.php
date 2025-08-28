<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class Decimal extends Constraint
{
    public function __construct(
        public string $translationPrefix,
        public int    $precision = 8, // The total number of digits allow, including decimal places
        public int    $scale = 1, // The number of decimal places
        public string $message = 'common.number.invalid',
        public string $maxMessageKey = 'too-big',
        public string $placesMessageKey = 'too-many-places',
        ?array        $groups = null,
        mixed         $payload = null
    ) {
        parent::__construct([], $groups, $payload);
    }
}
