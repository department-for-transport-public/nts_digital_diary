<?php

namespace App\Tests\Functional\OnBoarding\BugTests;

use ApiPlatform\Metadata\UrlGeneratorInterface;
use App\Entity\OtpUser;
use App\Tests\DataFixtures\OtpUserFixtures;
use App\Tests\Functional\AbstractProceduralWizardTest;
use App\Tests\Functional\Wizard\Form\FormTestCase;
use App\Utility\DirectOnboardingTestUrlGenerator;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;

/**
 */
class DirectOnboardingInvalidProxyBugTest extends AbstractProceduralWizardTest
{
    protected DirectOnboardingTestUrlGenerator $directOnboardingTestUrlGenerator;

    protected function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([OtpUserFixtures::class]);
        $this->context = $this->createContext('');
        $this->directOnboardingTestUrlGenerator = self::getContainer()->get(DirectOnboardingTestUrlGenerator::class);
    }

    protected function commonInitialActions(OtpUser $otpUser): void
    {
        $url = $this->directOnboardingTestUrlGenerator->generateUrl($otpUser, 1, urlGenerationMode: UrlGeneratorInterface::ABS_PATH);

        $this->client->request('GET', $url);
        $this->assertEquals("Access direct set-up", $this->getHeading());

        $this->formTestAction('/onboarding/direct', 'form_continue', [
            new FormTestCase([]),
        ]);

        $this->assertEquals("NTS digital diary direct set-up", $this->getHeading());

        $this->formTestAction('/onboarding/direct/workflow/introduction', 'form_button_group_continue', [
            new FormTestCase([]),
        ]);

        $this->formTestAction('/onboarding/direct/workflow/diary-keeper-logins', 'diary_keeper_logins_button_group_continue', [
            new FormTestCase([
                'diary_keeper_logins[diaryKeepers][1][user][username]' => 'bob@example.com',
                'diary_keeper_logins[diaryKeepers][3][user][username]' => 'daisy@example.com',
            ]),
        ]);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testEditLoginsToCauseProblem(): void
    {
        $otpUser = $this->fixtureReferenceRepository->getReference('user:otp', OtpUser::class);
        $otpUser = $this->entityManager->find(OtpUser::class, $otpUser->getId());
        $this->commonInitialActions($otpUser);
        $this->entityManager->refresh($otpUser);

        $household = $otpUser->getHousehold();
        $this->formTestAction('/onboarding/direct/workflow/diary-keeper-identity/1', 'user_identifier_button_group_continue', [
            new FormTestCase([
                'user_identifier[user][proxies][]' => [
                    $household->getDiaryKeeperByNumber(2)->getId(),
                    $household->getDiaryKeeperByNumber(4)->getId(),
                ],
            ]),
        ]);
        $this->assertCount(2, $household->getDiaryKeeperByNumber(1)->getProxies());

        $this->clickLinkContaining('Back');
        $this->clickLinkContaining('Back');

        // With the bug, this fails, as it says that daisy@example.com is already in use,
        // where in reality it should not, as we have emptied Alice's field
        $this->formTestAction('/onboarding/direct/workflow/diary-keeper-logins', 'diary_keeper_logins_button_group_continue', [
            new FormTestCase([
                'diary_keeper_logins[diaryKeepers][1][user][username]' => '',
            ]),
        ]);

        $this->entityManager->clear();
        $household = $this->entityManager->find(OtpUser::class, $otpUser->getId())->getHousehold();
        $this->assertEquals("Digital diary access details for Alice", $this->getHeading());
        $this->assertCount(1, $household->getDiaryKeeperByNumber(1)->getProxies());
    }


    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testEditIndividualUsernameToCauseProblem(): void
    {
        $otpUser = $this->fixtureReferenceRepository->getReference('user:otp', OtpUser::class);
        $otpUser = $this->entityManager->find(OtpUser::class, $otpUser->getId());
        $this->commonInitialActions($otpUser);
        $this->entityManager->refresh($otpUser);

        $household = $otpUser->getHousehold();
        $this->formTestAction('/onboarding/direct/workflow/diary-keeper-identity/1', 'user_identifier_button_group_continue', [
            new FormTestCase([
                'user_identifier[user][proxies][]' => [
                    $household->getDiaryKeeperByNumber(2)->getId(),
                    $household->getDiaryKeeperByNumber(4)->getId(),
                ],
            ]),
        ]);
        $this->assertCount(2, $household->getDiaryKeeperByNumber(1)->getProxies());

        $this->formTestAction('/onboarding/direct/workflow/diary-keeper-identity/2', 'user_identifier_button_group_continue', [
            new FormTestCase([
                'user_identifier[user][username]' => '',
                'user_identifier[user][proxies][]' => [
                    $household->getDiaryKeeperByNumber(4)->getId(),
                ],
            ], [
                '#user_identifier_user_username',
            ]),
        ]);

        $this->entityManager->clear();
        $household = $this->entityManager->find(OtpUser::class, $otpUser->getId())->getHousehold();
        $this->assertCount(2, $household->getDiaryKeeperByNumber(1)->getProxies());
    }
}
