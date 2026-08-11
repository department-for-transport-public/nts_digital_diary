<?php

namespace App\Tests\Functional\Interviewer\Training\Reflow;

use App\Entity\InterviewerTrainingRecord;
use App\Entity\Metrics\MetricsLog;
use App\Entity\User;
use App\Tests\DataFixtures\Reflow\UserFixtures;
use App\Tests\Functional\AbstractFunctionalTestCase;
use Doctrine\ORM\EntityManagerInterface;

class PersonalDiaryTest extends AbstractFunctionalTestCase
{
    private EntityManagerInterface $entityManager;

    public function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([
            UserFixtures::class
        ]);
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->loginUser('interviewer@example.com');
    }

    public function testPersonalDiary(): void
    {
        $this->client->get('/interviewer/training');

        $this->assertModuleHasState(InterviewerTrainingRecord::STATE_NEW);

        // find onboarding link
        $this->client->clickLink('Module 2: Personal travel diary');

        // find onboarding link
        $this->client->clickLink('Access personal travel diary');

        // add a journey via the combined reflow wizard
        $this->client->get('/travel-diary/reflow/day-1/new-add-journey');

        $this->client->submitForm('wizard_type[button_group][continue]', [
            'wizard_type[wizardType]' => 'add',
        ]);

        $this->client->submitForm('locations[button_group][continue]', [
            'locations[start_choice]' => 'home',
            'locations[end_choice]' => 'other',
            'locations[endLocation]' => 'somewhere',
        ]);

        $this->client->submitForm('journey_details[button_group][continue]', [
            "journey_details[times][startTime][hour]" => "1",
            "journey_details[times][startTime][minute]" => "59",
            "journey_details[times][startTime][am_or_pm]" => "pm",
            "journey_details[times][endTime][hour]" => "2",
            "journey_details[times][endTime][minute]" => "25",
            "journey_details[times][endTime][am_or_pm]" => "pm",
            "journey_details[purpose][purpose]" => "Eat breakfast",
        ]);

        // Add a minimal stage (required by the reflow wizard)
        $this->client->submitForm('method[button_group][continue]', [
            'method[method]' => '1', // walk
        ]);

        $this->client->submitForm('details[button_group][continue]', [
            'details[distanceTravelled][value]' => '1',
            'details[distanceTravelled][unit]' => 'miles',
            'details[travelTime]' => '10',
            'details[companions][adultCount]' => '1',
            'details[companions][childCount]' => '0',
        ]);

        $this->client->submitForm('add_another[button_group][continue]', [
            'add_another[is_final_stage]' => 'true',
        ]);

        $this->assertModuleHasState(InterviewerTrainingRecord::STATE_IN_PROGRESS);

        $this->client->get('/');

        $this->client->clickLink('Mark my travel diary as complete');
        $this->assertEquals('/travel-diary/mark-as-complete', $this->getUrlPath());

        $this->client->submitForm('confirm_action[button_group][confirm]');
        $this->assertEquals('/travel-diary', $this->getUrlPath());

        $this->assertModuleHasState(InterviewerTrainingRecord::STATE_COMPLETE);

        // 1 interviewer login, 1 impersonation
        $this->assertCount(2, $this->entityManager->getRepository(MetricsLog::class)->findAll());
    }

    protected function assertModuleHasState($expectedState)
    {
        $user = $this->entityManager->getRepository(User::class)->loadUserByIdentifier('interviewer@example.com');
        $trainingRecord = $user->getInterviewer()->getLatestTrainingRecordForModule(InterviewerTrainingRecord::MODULE_PERSONAL_TRAVEL_DIARY);
        $this->entityManager->refresh($trainingRecord);
        $this->assertEquals($expectedState, $trainingRecord->getState());
    }
}