<?php

namespace App\Tests\Functional\DiaryKeeper;

use App\Entity\Journey\Journey;
use App\Tests\DataFixtures\StageFixtures;
use App\Tests\Functional\AbstractWizardTest;
use App\Tests\Functional\Wizard\Action\CallbackAction;
use App\Tests\Functional\Wizard\Action\Context;
use App\Tests\Functional\Wizard\Action\FormTestAction;
use App\Tests\Functional\Wizard\Action\PathTestAction;
use App\Tests\Functional\Wizard\Form\FormTestCase;

class EditJourneyTest extends AbstractWizardTest
{
    public const string TEST_USERNAME = 'diary-keeper-adult@example.com';

    private function navigateAndClickChange(Journey $journey, string $changeSuffix): CallbackAction
    {
        return new CallbackAction(function (Context $context) use ($journey, $changeSuffix): void {
            $context->getClient()->request('GET', "/travel-diary/journey/{$journey->getId()}");
            $context->getTestCase()->clickChangeLinkFor($changeSuffix);
        });
    }

    public function testEditPurpose(): void
    {
        $this->initialiseClientAndLoadFixtures([StageFixtures::class]);
        $this->loginUser(self::TEST_USERNAME);

        $journey = $this->fixtureReferenceRepository->getReference('journey:1', Journey::class);
        $journeyId = $journey->getId();

        $this->doWizardTest([
            $this->navigateAndClickChange($journey, 'Journey purpose'),
            new FormTestAction(
                "/travel-diary/journey/{$journeyId}/edit/purpose",
                'purpose_button_group_continue',
                [new FormTestCase(['purpose[purpose]' => 'Visit the dentist'])]
            ),
            new PathTestAction("/travel-diary/journey/{$journeyId}"),
            new CallbackAction(function (Context $context) use ($journeyId): void {
                $journey = $context->getEntityManager()->getRepository(Journey::class)->find($journeyId);
                $context->getTestCase()->assertEquals('Visit the dentist', $journey->getPurpose());
            }),
        ]);
    }

    public function testEditStartLocationAndTime(): void
    {
        $this->initialiseClientAndLoadFixtures([StageFixtures::class]);
        $this->loginUser(self::TEST_USERNAME);

        // journey:1 starts at Home (start_choice=home). Switch to a named start location and change start time.
        $journey = $this->fixtureReferenceRepository->getReference('journey:1', Journey::class);
        $journeyId = $journey->getId();

        $this->doWizardTest([
            $this->navigateAndClickChange($journey, 'start location and time'),
            new FormTestAction(
                "/travel-diary/journey/{$journeyId}/edit/locations",
                'locations_button_group_continue',
                [new FormTestCase([
                    'locations[start_choice]' => 'other',
                    'locations[startLocation]' => 'New start factory',
                ])]
            ),
            new FormTestAction(
                "/travel-diary/journey/{$journeyId}/edit/times",
                'times_button_group_continue',
                [new FormTestCase([
                    'times[startTime][hour]' => '9',
                    'times[startTime][minute]' => '15',
                    'times[startTime][am_or_pm]' => 'am',
                ])]
            ),
            new PathTestAction("/travel-diary/journey/{$journeyId}"),
            new CallbackAction(function (Context $context) use ($journeyId): void {
                $journey = $context->getEntityManager()->getRepository(Journey::class)->find($journeyId);
                $testCase = $context->getTestCase();
                $testCase->assertFalse($journey->getIsStartHome());
                $testCase->assertEquals('New start factory', $journey->getStartLocation());
                $testCase->assertTimeEquals(new \DateTime('9:15am'), $journey->getStartTime());
            }),
        ]);
    }

    public function testEditEndLocationAndTime(): void
    {
        $this->initialiseClientAndLoadFixtures([StageFixtures::class]);
        $this->loginUser(self::TEST_USERNAME);

        // journey:1 ends at "Wobble". Change the end location and end time.
        $journey = $this->fixtureReferenceRepository->getReference('journey:1', Journey::class);
        $journeyId = $journey->getId();

        $this->doWizardTest([
            $this->navigateAndClickChange($journey, 'end location and time'),
            new FormTestAction(
                "/travel-diary/journey/{$journeyId}/edit/locations",
                'locations_button_group_continue',
                [new FormTestCase([
                    'locations[end_choice]' => 'other',
                    'locations[endLocation]' => 'Shiny new place',
                ])]
            ),
            new FormTestAction(
                "/travel-diary/journey/{$journeyId}/edit/times",
                'times_button_group_continue',
                [new FormTestCase([
                    'times[endTime][hour]' => '10',
                    'times[endTime][minute]' => '30',
                    'times[endTime][am_or_pm]' => 'am',
                ])]
            ),
            new PathTestAction("/travel-diary/journey/{$journeyId}"),
            new CallbackAction(function (Context $context) use ($journeyId): void {
                $journey = $context->getEntityManager()->getRepository(Journey::class)->find($journeyId);
                $testCase = $context->getTestCase();
                $testCase->assertFalse($journey->getIsEndHome());
                $testCase->assertEquals('Shiny new place', $journey->getEndLocation());
                $testCase->assertTimeEquals(new \DateTime('10:30am'), $journey->getEndTime());
            }),
        ]);
    }
}
