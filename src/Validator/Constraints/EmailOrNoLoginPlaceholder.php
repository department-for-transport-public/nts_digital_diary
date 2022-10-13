<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Email;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class EmailOrNoLoginPlaceholder extends Email
{
}