<?php

namespace App\Tests\Functional\Interviewer\Training;

use App\Entity\InterviewerTrainingRecord;
use App\Entity\User;
use App\Tests\DataFixtures\UserFixtures;
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

        // add a journey
        $this->client->get('/travel-diary/day-1/add-journey');

        $this->client->submitForm('locations[button_group][continue]', [
            'locations[start_choice]' => 'home',
            'locations[end_choice]' => 'other',
            'locations[endLocation]' => 'somewhere',
        ]);

        $this->client->submitForm('times[button_group][continue]', [
            "times[startTime][hour]" => "1",
            "times[startTime][minute]" => "59",
            "times[startTime][am_or_pm]" => "pm",
            "times[endTime][hour]" => "2",
            "times[endTime][minute]" => "25",
            "times[endTime][am_or_pm]" => "pm",
        ]);

        $this->client->submitForm('purpose[button_group][continue]', [
            "purpose[purpose]" => "Eat breakfast"
        ]);

        $this->assertModuleHasState(InterviewerTrainingRecord::STATE_IN_PROGRESS);

        $this->client->get('/');

        $this->client->clickLink('Mark travel diary as complete');
        $this->assertEquals('/travel-diary/mark-as-complete', $this->getUrlPath());

        $this->client->submitForm('confirm_action[button_group][confirm]');
        $this->assertEquals('/travel-diary', $this->getUrlPath());

        $this->assertModuleHasState(InterviewerTrainingRecord::STATE_COMPLETE);
    }

    protected function assertModuleHasState($expectedState)
    {
        $user = $this->entityManager->getRepository(User::class)->loadUserByIdentifier('interviewer@example.com');
        $trainingRecord = $user->getInterviewer()->getLatestTrainingRecordForModule(InterviewerTrainingRecord::MODULE_PERSONAL_TRAVEL_DIARY);
        $this->entityManager->refresh($trainingRecord);
        $this->assertEquals($expectedState, $trainingRecord->getState());
    }
}