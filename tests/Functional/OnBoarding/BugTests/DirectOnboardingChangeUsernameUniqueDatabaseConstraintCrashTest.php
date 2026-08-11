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
use PHPUnit\Framework\AssertionFailedError;

/**
 * This bug is uncannily similar to another previous one. When a username is moved up the list of diary keepers
 * (e.g. from the 2nd to the 1st DK) the database tries to update the earlier one before emptying the later one,
 * causing a UniqueConstraintViolation.
 * (The older similar bug was caused by Symfony's form validation, which queried the database to check for
 * duplicates and obviously found one)
 */
class DirectOnboardingChangeUsernameUniqueDatabaseConstraintCrashTest extends AbstractProceduralWizardTest
{
    protected DirectOnboardingTestUrlGenerator $directOnboardingTestUrlGenerator;

    protected function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([OtpUserFixtures::class]);
        $this->context = $this->createContext('');
        $this->directOnboardingTestUrlGenerator = self::getContainer()->get(DirectOnboardingTestUrlGenerator::class);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function testCrashDueToBug(): void
    {
        $otpUser = $this->fixtureReferenceRepository->getReference('user:otp', OtpUser::class);
        $otpUser = $this->entityManager->find(OtpUser::class, $otpUser->getId());
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

        $this->clickLinkContaining('Back');

        // With the bug, this fails, as it says that daisy@example.com is already in use,
        // where in reality it should not, as we have emptied Alice's field
        $this->formTestAction('/onboarding/direct/workflow/diary-keeper-logins', 'diary_keeper_logins_button_group_continue', [
            new FormTestCase([
                'diary_keeper_logins[diaryKeepers][0][user][username]' => 'daisy@example.com',
                'diary_keeper_logins[diaryKeepers][3][user][username]' => '',
            ]),
        ]);

        $this->entityManager->refresh($otpUser);
        $household = $otpUser->getHousehold();
        $this->assertEquals('bob@example.com', $household->getDiaryKeeperByNumber(2)->getUser()->getUsername());

        try {
            $elem = $this->getElementByTextContains("p", "A duplicate record already exists");
        } catch (AssertionFailedError) {} finally {
            if ($elem ?? false) {
                $this->fail("Duplicate record warning should not be displayed");
            }
        }

        $this->assertEquals("Digital diary access details for Alice", $this->getHeading());
    }
}
