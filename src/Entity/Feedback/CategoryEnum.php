<?php

namespace App\Entity\Feedback;

enum CategoryEnum:string {
    case Support = 'support';
    case Feedback = 'feedback';
}