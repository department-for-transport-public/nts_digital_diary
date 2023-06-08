<?php

namespace App\Tests\Security\Voter;

use App\Entity\DiaryKeeper;
use App\Entity\Household;
use App\Entity\Interviewer;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class EditDiaryVoterTest extends WebTestCase
{
    public function dataCanEditDiary(): array
    {
        $household = (new Household())
            ->setIsOnboardingComplete(true);

        $interviewer = (new Interviewer());
        $interviewerUser = (new User())
            ->setUsername('interviewer@example.com')
            ->setInterviewer($interviewer);

        $diaryKeeper = (new DiaryKeeper())
            ->setHousehold($household);
        $diaryKeeperUser = (new User())
            ->setUsername('dk@example.com')
            ->setDiaryKeeper($diaryKeeper);

        return [
            "DK, in progress" => [$diaryKeeper, $diaryKeeperUser, DiaryKeeper::STATE_IN_PROGRESS, true],
            "DK, completed" => [$diaryKeeper, $diaryKeeperUser, DiaryKeeper::STATE_COMPLETED, false],
            "DK, approved" => [$diaryKeeper, $diaryKeeperUser, DiaryKeeper::STATE_APPROVED, false],
            "DK, discarded" => [$diaryKeeper, $diaryKeeperUser, DiaryKeeper::STATE_DISCARDED, false],
            "Int, in progress" => [$diaryKeeper, $interviewerUser, DiaryKeeper::STATE_IN_PROGRESS, true],
            "Int, completed" => [$diaryKeeper, $interviewerUser, DiaryKeeper::STATE_COMPLETED, true],
            "Int, approved" => [$diaryKeeper, $interviewerUser, DiaryKeeper::STATE_APPROVED, false],
            "Int, discarded" => [$diaryKeeper, $interviewerUser, DiaryKeeper::STATE_DISCARDED, false],
        ];
    }

    /**
     * @dataProvider dataCanEditDiary
     */
    public function testCanChangeEmail(DiaryKeeper $diaryKeeper, User $user, $state, $expectedToBeAllowed): void
    {
        $client = static::createClient();
        $client->loginUser($user);
        $authChecker = $client->getContainer()->get(AuthorizationCheckerInterface::class);

        $diaryKeeper->setDiaryState($state);
        $this->assertEquals($expectedToBeAllowed, $authChecker->isGranted('EDIT_DIARY', $diaryKeeper));
    }
}