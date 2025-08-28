<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class CostOrNil extends Constraint
{
    public function __construct(
        public ?string $translationPrefix = null,
        public bool    $allowBlankHasCost = false, // Allow the radio/hasCost to be completely unselected?
        public bool    $allowBlankCost = false, // When the radio/hasCost is true, Allow the cost input to be empty?
        public int     $precision = 10,
        public int     $scale = 2,
        ?array         $groups = null,
        mixed          $payload = null
    ) {
        parent::__construct([], $groups, $payload);
    }
}