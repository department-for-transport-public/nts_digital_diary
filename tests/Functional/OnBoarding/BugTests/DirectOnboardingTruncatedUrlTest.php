<?php

namespace App\Tests\Functional\OnBoarding\BugTests;

use ApiPlatform\Metadata\UrlGeneratorInterface;
use App\Entity\OtpUser;
use App\Tests\DataFixtures\OtpUserFixtures;
use App\Tests\Functional\AbstractFunctionalWebTestCase;
use App\Utility\DirectOnboardingTestUrlGenerator;
use Symfony\Component\HttpFoundation\Request;

class DirectOnboardingTruncatedUrlTest extends AbstractFunctionalWebTestCase
{
    public function testTruncatedUrlShowsErrorWithManualOnboardingLink(): void
    {
        $this->initialiseClientAndLoadFixtures([OtpUserFixtures::class]);

        $urlGenerator = self::getContainer()->get(DirectOnboardingTestUrlGenerator::class);
        $otpUser = $this->fixtureReferenceRepository->getReference('user:otp', OtpUser::class);

        // Generate a valid direct onboarding URL, then truncate at '&dk' to simulate
        // a barcode scanner cutting the URL at the first '[' character
        $url = $urlGenerator->generateUrl($otpUser, 1, urlGenerationMode: UrlGeneratorInterface::ABS_PATH);
        $truncatedUrl = substr($url, 0, strpos($url, '&dk'));

        $this->client->request(Request::METHOD_GET, $truncatedUrl);
        $this->assertResponseIsSuccessful();

        $this->client->submitForm('form_continue');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('h1', 'Direct set-up error');

        // The error page should contain a link to the manual onboarding household wizard
        $manualLink = $this->client->getCrawler()->filter('a[href$="/onboarding/household"]');
        $this->assertGreaterThan(0, $manualLink->count(), 'Error page should contain a link to manual onboarding');
    }
}
