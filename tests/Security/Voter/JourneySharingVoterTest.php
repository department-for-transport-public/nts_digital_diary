<?php

namespace App\Tests\Security\Voter;

use App\Entity\DiaryDay;
use App\Entity\DiaryKeeper;
use App\Entity\Household;
use App\Entity\Journey\Journey;
use App\Entity\Journey\Stage;
use App\Entity\User;
use App\Security\Voter\TravelDiary\JourneySharingVoter;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class JourneySharingVoterTest extends WebTestCase
{
    public function createJourney(?int $adultCount, ?int $childCount, int $totalStages, bool $isShared=false, bool $isItselfASharedJourney=false): Journey
    {
        $diaryDay = (new DiaryDay())->setDiaryKeeper(new DiaryKeeper());
        $journey = (new Journey())->setDiaryDay($diaryDay);

        for ($i = 0; $i < $totalStages; $i++) {
            $stage = (new Stage())
                ->setAdultCount($adultCount)
                ->setChildCount($childCount);

            $journey->addStage($stage);
        }

        if ($isShared) {
            $sharedToDiaryDay = (new DiaryDay())
                ->setDiaryKeeper(new DiaryKeeper());

            $sharedTo = (new Journey())
                ->setDiaryDay($sharedToDiaryDay);

            $journey->addSharedTo($sharedTo);
        }

        if ($isItselfASharedJourney) {
            $sharedFromDiaryDay = (new DiaryDay())
                ->setDiaryKeeper(new DiaryKeeper());

            (new Journey())
                ->setDiaryDay($sharedFromDiaryDay)
                ->addSharedTo($journey);
        }

        return $journey;
    }


    protected function setupHousehold(bool $journeySharingEnabled, bool $userIsProxying): User
    {
        $otherDiaryKeeper = (new DiaryKeeper());
        $userDiaryKeeper = (new DiaryKeeper());

        (new Household())
            ->addDiaryKeeper($otherDiaryKeeper)
            ->addDiaryKeeper($userDiaryKeeper)
            ->setIsJourneySharingEnabled($journeySharingEnabled);

        $user = (new User())
            ->setDiaryKeeper($userDiaryKeeper);

        if ($userIsProxying) {
            $userDiaryKeeper->addActingAsProxyFor($otherDiaryKeeper);
        }

        return $user;
    }

    public function getCanShareJourneyTests(bool $journeySharingEnabled, bool $userIsProxying, bool $sourceAlreadyShared, bool $sourceIsItselfASharedJourney): array
    {
        $user = $this->setupHousehold($journeySharingEnabled, $userIsProxying);

        $expectedSuccess = ($journeySharingEnabled || $userIsProxying) && !$sourceAlreadyShared && !$sourceIsItselfASharedJourney;
        return [
            [$user, $this->createJourney(1, 1, 1, $sourceAlreadyShared, $sourceIsItselfASharedJourney), $expectedSuccess],
            [$user, $this->createJourney(2, 0, 1, $sourceAlreadyShared, $sourceIsItselfASharedJourney), $expectedSuccess],
            [$user, $this->createJourney(0, 2, 1, $sourceAlreadyShared, $sourceIsItselfASharedJourney), $expectedSuccess],
            [$user, $this->createJourney(1, 1, 2, $sourceAlreadyShared, $sourceIsItselfASharedJourney), $expectedSuccess],
            [$user, $this->createJourney(2, 0, 5, $sourceAlreadyShared, $sourceIsItselfASharedJourney), $expectedSuccess],
            [$user, $this->createJourney(0, 2, 5, $sourceAlreadyShared, $sourceIsItselfASharedJourney), $expectedSuccess],
        ];
    }

    public function dataInvalidJourneys(): array
    {
        $user = $this->setupHousehold(true, true);

        return [
            [$user, $this->createJourney(null, null, 0), false],
            [$user, $this->createJourney(null, null, 1), false],
            [$user, $this->createJourney(1, 0, 1), false],
            [$user, $this->createJourney(0, 1, 1), false],
            [$user, $this->createJourney(1, 0, 5), false],
            [$user, $this->createJourney(0, 1, 5), false],
        ];
    }

    /**
     * @dataProvider dataInvalidJourneys
     */
    public function testInvalidJourneys(User $user, Journey $journey, bool $expectedToBeAllowed): void
    {
        $this->baseCanShareJourneyTest($user, $journey, $expectedToBeAllowed);
    }

    public function dataCanShareJourneyNoProxyNoSharing(): array
    {
        return $this->getCanShareJourneyTests(false, false, false, false);
    }

    /**
     * @dataProvider dataCanShareJourneyNoProxyNoSharing
     */
    public function testCanShareJourneyNoProxyNoSharing(User $user, Journey $journey, bool $expectedToBeAllowed): void
    {
        $this->baseCanShareJourneyTest($user, $journey, $expectedToBeAllowed);
    }

    public function dataCanShareJourneyProxyNoSharing(): array
    {
        return $this->getCanShareJourneyTests(false, true, false, false);
    }

    /**
     * @dataProvider dataCanShareJourneyProxyNoSharing
     */
    public function testCanShareJourneyProxyNoSharing(User $user, Journey $journey, bool $expectedToBeAllowed): void
    {
        $this->baseCanShareJourneyTest($user, $journey, $expectedToBeAllowed);
    }

    public function dataCanShareJourneySharingNoProxy(): array
    {
        return $this->getCanShareJourneyTests(true, false, false, false);
    }

    /**
     * @dataProvider dataCanShareJourneySharingNoProxy
     */
    public function testCanShareJourneySharingNoProxy(User $user, Journey $journey, bool $expectedToBeAllowed): void
    {
        $this->baseCanShareJourneyTest($user, $journey, $expectedToBeAllowed);
    }

    public function dataCanShareJourneyProxyAndSharing(): array
    {
        return $this->getCanShareJourneyTests(true, true, false, false);
    }

    /**
     * @dataProvider dataCanShareJourneyProxyAndSharing
     */
    public function testCanShareJourneyProxyAndSharing(User $user, Journey $journey, bool $expectedToBeAllowed): void
    {
        $this->baseCanShareJourneyTest($user, $journey, $expectedToBeAllowed);
    }

    public function dataCanShareJourneySharingAlreadyShared(): array
    {
        return $this->getCanShareJourneyTests(true, false, true, false);
    }

    /**
     * @dataProvider dataCanShareJourneySharingAlreadyShared
     */
    public function testCanShareJourneySharingAlreadyShared(User $user, Journey $journey, bool $expectedToBeAllowed): void
    {
        $this->baseCanShareJourneyTest($user, $journey, $expectedToBeAllowed);
    }

    public function dataCanShareJourneySharingThatIsItselfShared(): array
    {
        return $this->getCanShareJourneyTests(true, false, false, true);
    }

    /**
     * @dataProvider dataCanShareJourneySharingThatIsItselfShared
     */
    public function testCanShareJourneyThatIsItselfShared(User $user, Journey $journey, bool $expectedToBeAllowed): void
    {
        $this->baseCanShareJourneyTest($user, $journey, $expectedToBeAllowed);
    }

    protected function baseCanShareJourneyTest(User $user, Journey $journey, bool $expectedToBeAllowed): void
    {
        $client = static::createClient();
        $client->loginUser($user);
        $authChecker = $client->getContainer()->get(AuthorizationCheckerInterface::class);

        $this->assertEquals($expectedToBeAllowed, $authChecker->isGranted(JourneySharingVoter::CAN_SHARE_JOURNEY, $journey));
    }

    public function dataCanShareWithDiaryKeeper(): array
    {

        $household = (new Household());
        $sharingEnabledHousehold = (new Household())
            ->setIsJourneySharingEnabled(true);

        $proxiedDiaryKeeper = (new DiaryKeeper())
            ->setHousehold($household);

        $nonProxiedDiaryKeeper = (new DiaryKeeper())
            ->setHousehold($household);

        $sharingEnabledProxiedDiaryKeeper = (new DiaryKeeper())
            ->setHousehold($sharingEnabledHousehold);

        $sharingEnabledNonProxiedDiaryKeeper = (new DiaryKeeper())
            ->setHousehold($sharingEnabledHousehold);

        $sharingEnabledCompleteDiaryKeeper = (new DiaryKeeper())
            ->setDiaryState(DiaryKeeper::STATE_COMPLETED)
            ->setHousehold($sharingEnabledHousehold);
        $sharingEnabledApprovedDiaryKeeper = (new DiaryKeeper())
            ->setDiaryState(DiaryKeeper::STATE_APPROVED)
            ->setHousehold($sharingEnabledHousehold);
        $sharingEnabledDiscardedDiaryKeeper = (new DiaryKeeper())
            ->setDiaryState(DiaryKeeper::STATE_DISCARDED)
            ->setHousehold($sharingEnabledHousehold);
        $sharingEnabledInProgressDiaryKeeper = (new DiaryKeeper())
            ->setDiaryState(DiaryKeeper::STATE_IN_PROGRESS)
            ->setHousehold($sharingEnabledHousehold);

        $userDiaryKeeper = (new DiaryKeeper())
            ->addActingAsProxyFor($proxiedDiaryKeeper)
            ->addActingAsProxyFor($sharingEnabledProxiedDiaryKeeper)
            ->setHousehold($household);

        $user = (new User())->setDiaryKeeper($userDiaryKeeper);

        return [
            [$user, $proxiedDiaryKeeper, true],
            [$user, $nonProxiedDiaryKeeper, false],
            [$user, $sharingEnabledProxiedDiaryKeeper, true],
            [$user, $sharingEnabledNonProxiedDiaryKeeper, true],
            [$user, $sharingEnabledCompleteDiaryKeeper, false],
            [$user, $sharingEnabledApprovedDiaryKeeper, false],
            [$user, $sharingEnabledDiscardedDiaryKeeper, false],
            [$user, $sharingEnabledInProgressDiaryKeeper, true],
        ];
    }

    /**
     * @dataProvider dataCanShareWithDiaryKeeper
     */
    public function testCanShareWithDiaryKeeper(User $user, DiaryKeeper $diaryKeeper, bool $expectedToBeAllowed): void
    {
        $client = static::createClient();
        $client->loginUser($user);
        $authChecker = $client->getContainer()->get(AuthorizationCheckerInterface::class);

        $this->assertEquals($expectedToBeAllowed, $authChecker->isGranted(JourneySharingVoter::CAN_SHARE_WITH_DIARY_KEEPER, $diaryKeeper));
    }
}