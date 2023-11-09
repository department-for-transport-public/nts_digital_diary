<?php

namespace App\Tests\Security\Voter;

use App\Entity\DiaryKeeper;
use App\Entity\Household;
use App\Entity\Interviewer;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class EmailChangeVoterTest extends WebTestCase
{
    public function generateData(bool $withIdentifierForLogin): array
    {
        $userName = fn(string $email) => $withIdentifierForLogin ? $email : ('no-login:'.md5($email));

        $household = (new Household())
            ->setIsOnboardingComplete(true);

        $interviewer = (new Interviewer());
        $interviewerUser = (new User())
            ->setUsername($userName('interviewer@example.com'))
            ->setInterviewer($interviewer);

        $diaryKeeper = (new DiaryKeeper())
            ->setHousehold($household);
        $diaryKeeperUser = (new User())
            ->setUsername($userName('dk@example.com'))
            ->setDiaryKeeper($diaryKeeper);

        // -----

        $nonProxiedDiaryKeeperWithoutPw = (new DiaryKeeper())
            ->setHousehold($household);
        $nonProxiedDiaryKeeperUserWithoutPw = (new User())
            ->setUsername($userName('non-proxied-dk@example.com'))
            ->setDiaryKeeper($nonProxiedDiaryKeeperWithoutPw);

        $proxiedDiaryKeeperWithoutPw = (new DiaryKeeper())
            ->setHousehold($household);
        $proxiedDiaryKeeperUserWithoutPw = (new User())
            ->setUsername($userName('proxied-dk@example.com'))
            ->setDiaryKeeper($proxiedDiaryKeeperWithoutPw);
        $nonProxiedDiaryKeeperWithoutPw->addActingAsProxyFor($proxiedDiaryKeeperWithoutPw);

        $nonProxiedDiaryKeeperWithPassword = (new DiaryKeeper())
            ->setHousehold($household);
        $nonProxiedDiaryKeeperWithPasswordUser = (new User())
            ->setUsername($userName('non-proxied-dk-with-pw@example.com'))
            ->setDiaryKeeper($nonProxiedDiaryKeeperWithPassword)
            ->setPassword('this-is-a-password-hash');

        $proxiedDiaryKeeperWithPassword = (new DiaryKeeper())
            ->setHousehold($household);
        $proxiedDiaryKeeperWithPasswordUser = (new User())
            ->setUsername($userName('proxied-dk-with-pw@example.com'))
            ->setDiaryKeeper($proxiedDiaryKeeperWithPassword)
            ->setPassword('this-is-a-password-hash');
        $nonProxiedDiaryKeeperWithoutPw->addActingAsProxyFor($proxiedDiaryKeeperWithPassword);

        $loginStr = $withIdentifierForLogin ? 'login identifier' : 'no login identifier';

        return [
            "User DK / subject DK, no proxy, {$loginStr}, no password" => [$diaryKeeperUser, $nonProxiedDiaryKeeperUserWithoutPw, false],
            "User DK / subject DK, proxy, {$loginStr}, no password" => [$diaryKeeperUser, $proxiedDiaryKeeperUserWithoutPw, false],
            "User DK / subject DK, no proxy, {$loginStr}, password" => [$diaryKeeperUser, $nonProxiedDiaryKeeperWithPasswordUser, false],
            "User DK / subject DK, proxy, {$loginStr}, password" => [$diaryKeeperUser, $proxiedDiaryKeeperWithPasswordUser, false],

            "User Int / subject DK, no proxy, {$loginStr}, no password" => [$interviewerUser, $nonProxiedDiaryKeeperUserWithoutPw, true],
            "User Int / subject DK, proxy, {$loginStr}, no password" => [$interviewerUser, $proxiedDiaryKeeperUserWithoutPw, true],
            "User Int / subject DK, no proxy, {$loginStr}, password" => [$interviewerUser, $nonProxiedDiaryKeeperWithPasswordUser, false],
            "User Int / subject DK, proxy, {$loginStr}, password" => [$interviewerUser, $proxiedDiaryKeeperWithPasswordUser, false],
        ];
    }

    public function dataCanChangeEmail(): array
    {
        return array_merge(
            $this->generateData(true),
            $this->generateData(false),
        );
    }

    /**
     * @dataProvider dataCanChangeEmail
     */
    public function testCanChangeEmail(User $userAttemptingToDoThis, User $userWhoseEmailWillBeChanged, bool $expectedToBeAllowed): void
    {
        $client = static::createClient();
        $client->loginUser($userAttemptingToDoThis);
        $authChecker = $client->getContainer()->get(AuthorizationCheckerInterface::class);

        $this->assertEquals($expectedToBeAllowed, $authChecker->isGranted('EMAIL_CHANGE', $userWhoseEmailWillBeChanged));
    }
}