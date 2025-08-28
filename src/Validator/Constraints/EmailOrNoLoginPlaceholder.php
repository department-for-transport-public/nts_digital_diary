<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Email;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class EmailOrNoLoginPlaceholder extends Email
{
}