<?php

namespace App\Tests\Functional\OnBoarding\BugTests;

use App\Entity\Household;
use App\Entity\OtpUser;
use App\Security\OneTimePassword\PasscodeGenerator;
use App\Tests\DataFixtures\OtpUserFixtures;
use App\Tests\Functional\AbstractFunctionalWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Bug: an interviewer could add a diary keeper, reach /onboarding/submit,
 * delete the diary keeper in a second tab, and then submit the (now empty)
 * household — bypassing the client-side "Submit" button gate on the
 * dashboard. The household ended up marked onboarded with zero diary keepers.
 *
 * This test exercises the server-side state that results from that scenario
 * using the partially-onboarded OTP user fixture (has a household, no DKs).
 */
class SubmitEmptyHouseholdTest extends AbstractFunctionalWebTestCase
{
    public function testCannotSubmitHouseholdWithNoDiaryKeepers(): void
    {
        $this->initialiseClientAndLoadFixtures([OtpUserFixtures::class]);

        $otpUser = $this->fixtureReferenceRepository->getReference('user:otp:partially-onboarded', OtpUser::class);
        $household = $otpUser->getHousehold();
        $this->assertNotNull($household);
        $this->assertCount(0, $household->getDiaryKeepers(), 'Fixture precondition: household has no diary keepers');
        $this->assertFalse($household->getIsOnboardingComplete(), 'Fixture precondition: household is not yet onboarded');
        $householdId = $household->getId();

        // Log in as the OTP user via the manual-onboarding login form
        $passcodeGenerator = self::getContainer()->get(PasscodeGenerator::class);
        $this->client->request(Request::METHOD_GET, '/onboarding/login');
        $this->client->submitForm('otp_login_sign_in', [
            'otp_login[group][identifier]' => $otpUser->getUserIdentifier(),
            'otp_login[group][passcode]' => $passcodeGenerator->getPasswordForUserIdentifier($otpUser->getUserIdentifier()),
        ]);
        $this->client->followRedirect();

        // Navigate to the submit/review page and submit the confirm form
        $this->client->request(Request::METHOD_GET, '/onboarding/submit');
        $this->assertResponseIsSuccessful();

        $this->client->submitForm('confirm_household[button_group][confirm]');

        // Should NOT have redirected back to the dashboard (i.e. submission rejected)
        $this->assertFalse(
            $this->client->getResponse()->isRedirect('/onboarding'),
            'Submitting an empty household should not advance to the dashboard'
        );

        // Re-fetch and confirm the household was not marked onboarded
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $refreshed = $em->find(Household::class, $householdId);
        $this->assertFalse(
            $refreshed->getIsOnboardingComplete(),
            'Household should not be marked onboarding-complete when empty'
        );
    }
}
