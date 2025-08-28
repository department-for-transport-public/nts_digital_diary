<?php

namespace App\EventSubscriber\Onboarding;

enum OnboardingType: string
{
    case DIRECT = 'direct';
    case LEGACY = 'legacy';
    case CLI_ADD_HOUSEHOLD = 'cli-add-household';
}
