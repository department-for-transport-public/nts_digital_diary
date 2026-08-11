<?php

namespace App\Tests\Utility;

use App\Entity\AreaPeriod;
use App\Entity\DiaryKeeper;
use App\Entity\Household;
use App\Features;
use App\Utility\DiaryKeeperFeaturesService;
use Ghost\GovUkCoreBundle\Features as CoreFeatures;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Bundle\SecurityBundle\Security;

class DiaryKeeperFeaturesServiceTest extends TestCase
{
    protected function getService(): DiaryKeeperFeaturesService
    {
        return new DiaryKeeperFeaturesService($this->createMock(Security::class));
    }

    protected function getDiaryKeeper(): DiaryKeeper
    {
        return (new DiaryKeeper())
            ->setName('Test Diary Keeper')
            ->setNumber(1)
            ->setIsAdult(true)
            ->setMediaType(DiaryKeeper::MEDIA_TYPE_DIGITAL);
    }

    protected function getHousehold(): Household
    {
        return (new Household())
            ->setAddressNumber(1)
            ->setHouseholdNumber(1);
    }

    protected function getAreaPeriod(string $area): AreaPeriod
    {
        return (new AreaPeriod())
            ->setArea($area)
            ->populateMonthAndYearFromArea();
    }


    public function getEnabledForYearData(): array
    {
        return [
            ['601500', true],
            ['512500', false]
        ];
    }

    /**
     * @dataProvider getEnabledForYearData
     */
    public function testEnabledForYear($area, bool $expectedResult): void
    {
        $diaryKeeper = $this->getDiaryKeeper();
        $household = ($this->getHousehold())->addDiaryKeeper($diaryKeeper);
        ($this->getAreaPeriod($area))->addHousehold($household);

        $service = $this->getService();
        $this->assertSame($service->isEnabled(DiaryKeeperFeaturesService::TEST__ENABLED_FOR_2026, $diaryKeeper), $expectedResult);
    }

    /**
     * Feature::isEnabled() memoises its result in a static cache, so seed that cache
     * directly to control the FORCE_REFLOW_JOURNEY_WIZARD / REFLOW_TESTING flags per test.
     */
    protected function setGlobalFeatures(bool $forceReflow, bool $reflowTesting): void
    {
        (new ReflectionProperty(CoreFeatures::class, 'enabledFeatures'))->setValue(null, [
            Features::FORCE_REFLOW_JOURNEY_WIZARD => $forceReflow,
            Features::REFLOW_TESTING => $reflowTesting,
        ]);
    }

    protected function tearDown(): void
    {
        // Reset the static feature cache so it can't leak into other tests.
        (new ReflectionProperty(CoreFeatures::class, 'enabledFeatures'))->setValue(null, []);
        parent::tearDown();
    }

    public function getReflowWizardData(): array
    {
        // [area, forceReflow, reflowTesting, expectedReflowWizard]
        return [
            // reflow-testing on: areas ending 000-499 get the legacy wizard...
            'reflow-testing, sample 000 -> legacy' => ['601000', false, true, false],
            'reflow-testing, sample 499 -> legacy' => ['601499', false, true, false],
            // ...and areas ending 500-999 get the reflow wizard
            'reflow-testing, sample 500 -> reflow' => ['601500', false, true, true],
            'reflow-testing, sample 999 -> reflow' => ['601999', false, true, true],

            // reflow-testing off: the sample split does not apply, everyone stays on legacy
            'no flags, sample 000 -> legacy'       => ['601000', false, false, false],
            'no flags, sample 999 -> legacy'       => ['601999', false, false, false],

            // force-reflow supersedes reflow-testing: always the reflow wizard, even for a
            // legacy (000-499) sample and regardless of whether reflow-testing is enabled
            'force supersedes reflow-testing'      => ['601000', true, true, true],
            'force supersedes, reflow-testing off' => ['601499', true, false, true],
        ];
    }

    /**
     * @dataProvider getReflowWizardData
     */
    public function testReflowTestingWizardSelection(string $area, bool $forceReflow, bool $reflowTesting, bool $expectedReflowWizard): void
    {
        $this->setGlobalFeatures($forceReflow, $reflowTesting);

        $diaryKeeper = $this->getDiaryKeeper();
        $household = ($this->getHousehold())->addDiaryKeeper($diaryKeeper);
        ($this->getAreaPeriod($area))->addHousehold($household);

        $service = $this->getService();
        $this->assertSame(
            $expectedReflowWizard,
            $service->isEnabled(DiaryKeeperFeaturesService::JOURNEY_WIZARD_REFLOW, $diaryKeeper),
        );
    }
}