<?php

namespace App;

use ReflectionClass;
use RuntimeException;

class Features
{
    // Features activated by the presence of specific env vars
    // Prefix values with AUTOENV
    public const GAE_ENVIRONMENT = 'AUTOENV-gae-environment';
    private const AUTO_ENV_MAP = [
        'GAE_INSTANCE' => self::GAE_ENVIRONMENT,
    ];

    // Features enabled by the APP_FEATURES evn var
    public const ACCESSIBILITY_FIXTURES = 'accessibility-fixtures';
    public const CHECK_LETTER = 'check-letter';
    public const DEMO_FIXTURES = 'demo-fixtures';
    public const FORM_ERROR_LOG = 'form-error-log';
    public const PENTEST_FIXTURES = 'pentest-fixtures';
    public const SMARTLOOK_SESSION_RECORDING = 'smartlook-session-recording';
    public const REVEAL_INVITE_LINKS = 'reveal-invite-links';
    public const SHOW_ONBOARDING_CODES = 'show-onboarding-codes';

    private static array $enabledFeatures;

    public static function isEnabled(string $feature, bool $checkFeatureIsValid = false): bool
    {
        if ($checkFeatureIsValid) {
            self::checkFeatureIsValid($feature);
        }
        return in_array($feature, self::getEnabledFeatures());
    }

    private static function checkFeatureIsValid(string $feature): void
    {
        $allFeatures = self::getAppFeatureMap() + array_values(self::AUTO_ENV_MAP);

        if (!in_array($feature, $allFeatures)) {
            throw new RuntimeException("Unknown feature '${feature}'");
        }
    }

    public static function getEnabledFeatures(): array
    {
        if (!isset(self::$enabledFeatures)) {
            self::processFeatures();
        }
        return self::$enabledFeatures;
    }


    protected static function processFeatures()
    {
        self::$enabledFeatures = array_merge(self::detectAutoEnvFeatures(), self::detectAppFeatures());
    }

    protected static function detectAppFeatures(): array
    {
        $enableFeatures = explode(',', $_ENV['APP_FEATURES'] ?? '');

        $enableFeatures = array_filter($enableFeatures, fn($v) => !empty($v));
        $featureMap = self::getAppFeatureMap();
        if (!empty($badFeatures = array_diff($enableFeatures, $featureMap))) {
            throw new RuntimeException('Trying to enable unknown features: ' . join(', ', $badFeatures));
        }
        return array_intersect($featureMap, $enableFeatures);
    }

    protected static function getAppFeatureMap(): array
    {
        $oClass = new ReflectionClass(__CLASS__);
        $map = $oClass->getConstants();
        unset($map['AUTO_ENV_MAP']);
        return array_filter($map, fn($v) => !preg_match('/^AUTOENV-/', $v));
    }

    private static function detectAutoEnvFeatures(): array
    {
        $enabledFeatures = [];
        foreach (self::AUTO_ENV_MAP as $envVar => $feature) {
            if (getenv($envVar)) $enabledFeatures[] = $feature;
        }
        return $enabledFeatures;
    }
}