<?php

namespace App\Tests\Functional\Interviewer\Training;

use App\Entity\InterviewerTrainingRecord;
use App\Entity\Metrics\MetricsLog;
use App\Entity\User;
use App\Tests\DataFixtures\UserFixtures;
use App\Tests\Functional\AbstractFunctionalTestCase;
use Doctrine\ORM\EntityManagerInterface;

class DiaryCorrectionTest extends AbstractFunctionalTestCase
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

    public function testDiaryCorrection(): void
    {
        $this->client->get('/interviewer/training');

        // find onboarding link
        $this->client->clickLink('Module 4: Correcting, approving and submitting a travel diary');

        // find onboarding link
        $this->client->clickLink('Access travel diary for correction');
        $this->assertModuleHasState(InterviewerTrainingRecord::STATE_NEW);

        $this->impersonateAndApproveDiaryKeeper('Mary', true);
        $this->impersonateAndApproveDiaryKeeper('John');

        // submit household
        $this->client->clickLink('Submit for processing');
        $this->client->submitForm('confirm_action[button_group][confirm]');

        $this->assertModuleHasState(InterviewerTrainingRecord::STATE_COMPLETE);

        // 1 interviewer login, 2 impersonations
        $this->assertCount(3, $this->entityManager->getRepository(MetricsLog::class)->findAll());
    }

    protected function impersonateAndApproveDiaryKeeper($name, bool $checkModuleStatus = false,): void
    {
        $this->client->clickLink("Impersonate {$name}");

        if ($checkModuleStatus) $this->assertModuleHasState(InterviewerTrainingRecord::STATE_IN_PROGRESS);

        $this->assertEquals('/travel-diary', $this->getUrlPath());

        $this->client->clickLink('Return to module');

        $this->client->clickLink("Details / actions for {$name}");
        $this->client->clickLink("Mark diary as approved");

        $this->client->submitForm('approve_diary_confirm_action[button_group][confirm]', [
            "approve_diary_confirm_action[verifyEmptyDays]" => "1",
        ]);
    }

    protected function assertModuleHasState($expectedState): void
    {
        $user = $this->entityManager->getRepository(User::class)->loadUserByIdentifier('interviewer@example.com');
        $trainingRecord = $user->getInterviewer()->getLatestTrainingRecordForModule(InterviewerTrainingRecord::MODULE_DIARY_CORRECTION);
        $this->entityManager->refresh($trainingRecord);
        $this->assertEquals($expectedState, $trainingRecord->getState());
    }
}