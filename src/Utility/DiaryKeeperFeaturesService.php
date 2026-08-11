<?php

namespace App\Utility;

use App\Entity\DiaryKeeper;
use App\Entity\User;
use App\Features;
use ReflectionClass;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;

class DiaryKeeperFeaturesService
{
    public const string REFLOW_TEST_AREA = '111113';
    public function __construct(protected Security $security) {}

    protected function getCallback($feature): \Closure|array
    {
        return match($feature) {
            /* examples
            self::A                         => self::staticCallback(...),
            self::B                         => [self::staticCallback(...), ['arg1' => 'val']],
            */
            self::TEST__ENABLED_FOR_2026    => [$this->isEnabledForAreaStartingYear(...), ['year' => 2026]],

            self::JOURNEY_WIZARD_REFLOW     => $this->isUseReflowJourneyWizard(...),

            default => throw new RuntimeException("Feature {$feature} is not defined"),
        };
    }

    public const string TEST__ENABLED_FOR_2026 = 'email-reminders';
    public const string JOURNEY_WIZARD_REFLOW = 'journey-wizard-reflow';

    protected array $enabledFeatures;

    protected function getDiaryKeeper(): DiaryKeeper
    {
        $user = $this->security->getUser();
        if (!$user instanceof User || !$user->getDiaryKeeper()) { throw new RuntimeException('User is not a DiaryKeeper'); }
        return $user->getDiaryKeeper();
    }

    public function isEnabledForCurrentUser(string $feature): bool
    {
        return $this->isEnabled($feature, $this->getDiaryKeeper());
    }
    public function isEnabled(string $feature, DiaryKeeper $diaryKeeper): bool
    {
        if (!isset($this->enabledFeatures[$featureIdx = "{$feature}::{$diaryKeeper->getId()}"]))
        {
            $this->checkFeatureIsDefined($feature);
            $this->enabledFeatures[$featureIdx] = $this->getFeatureState($feature, $diaryKeeper);
        }
        return $this->enabledFeatures[$featureIdx];
    }

    protected function checkFeatureIsDefined(string $feature): void
    {
        $oClass = new ReflectionClass(self::class);
        if (!in_array($feature, $oClass->getConstants()))
        {
            throw new RuntimeException("Unknown feature '{$feature}'");
        }
    }

    protected function getFeatureState(string $feature, DiaryKeeper $diaryKeeper): bool
    {
        if (is_array($callback = $this->getCallback($feature)))
        {
            [$callback, $args] = $callback;
        }
        return $callback(...array_merge(['diaryKeeper' => $diaryKeeper], $args ?? []));
    }


    // -----
    // Callback methods

    protected function isEnabledForAreaStartingYear(DiaryKeeper $diaryKeeper, int $year): bool
    {
        return $diaryKeeper->getHousehold()?->getAreaPeriod()?->getYear() >= $year;
    }

    protected function isInterviewerTraining(DiaryKeeper $diaryKeeper): bool
    {
        return $diaryKeeper->getHousehold()?->getAreaPeriod()?->isTrainingArea();
    }

    protected function isUseReflowJourneyWizard(DiaryKeeper $diaryKeeper): bool
    {
        $areaSample = substr($diaryKeeper->getHousehold()?->getAreaPeriod()?->getArea(), 3, 3);

        return Features::isEnabled(Features::FORCE_REFLOW_JOURNEY_WIZARD)
            || $diaryKeeper->getHousehold()?->getAreaPeriod()?->getArea() === self::REFLOW_TEST_AREA
            || (Features::isEnabled(Features::REFLOW_TESTING) && intval($areaSample) >= 500)
            ;
    }
}