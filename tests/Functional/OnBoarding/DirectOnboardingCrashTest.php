<?php

namespace App\Tests\Functional\OnBoarding;

use ApiPlatform\Metadata\UrlGeneratorInterface;
use App\Entity\OtpUser;
use App\Tests\DataFixtures\OtpUserFixtures;
use App\Tests\Functional\AbstractProceduralWizardTest;
use App\Tests\Functional\Wizard\Form\FormTestCase;
use App\Utility\DirectOnboardingTestUrlGenerator;

class DirectOnboardingCrashTest extends AbstractProceduralWizardTest
{
    protected DirectOnboardingTestUrlGenerator $directOnboardingTestUrlGenerator;

    protected function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([OtpUserFixtures::class]);
        $this->context = $this->createContext('');
        $this->directOnboardingTestUrlGenerator = self::getContainer()->get(DirectOnboardingTestUrlGenerator::class);
    }

    public function testCrashDueToBug(): void
    {
        $otpUser = $this->fixtureReferenceRepository->getReference('user:otp', OtpUser::class);
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
                'diary_keeper_logins[diaryKeepers][0][user][username]' => 'daisy@example.com',
            ]),
        ]);

        $this->clickLinkContaining('Back');

        // With the bug, this fails, as it says that daisy@example.com is already in use,
        // where in reality it should not, as we have emptied Alice's field
        $this->formTestAction('/onboarding/direct/workflow/diary-keeper-logins', 'diary_keeper_logins_button_group_continue', [
            new FormTestCase([
                'diary_keeper_logins[diaryKeepers][0][user][username]' => '',
                'diary_keeper_logins[diaryKeepers][3][user][username]' => 'daisy@example.com',
            ]),
        ]);
    }
}
