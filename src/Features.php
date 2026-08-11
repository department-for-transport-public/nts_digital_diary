<?php

namespace App;

use Ghost\GovUkCoreBundle\Features as CoreFeatures;

class Features extends CoreFeatures
{
    // -----
    // Features enabled by the APP_FEATURES evn var

    public const string CHECK_LETTER = 'check-letter';
    public const string FORM_ERROR_LOG = 'form-error-log';
    public const string MILOMETER = 'milometer';
    public const string SMARTLOOK_SESSION_RECORDING = 'smartlook-session-recording';
    public const string REVEAL_INVITE_LINKS = 'reveal-invite-links';
    public const string SHOW_ONBOARDING_CODES = 'show-onboarding-codes';
    public const string ALLOW_DIRECT_ONBOARDING_DEBUG = 'allow-direct-onboarding-debug';
    public const string FORCE_REFLOW_JOURNEY_WIZARD = 'force-reflow-journey-wizard';
    public const string REFLOW_TESTING = 'reflow-testing';
}