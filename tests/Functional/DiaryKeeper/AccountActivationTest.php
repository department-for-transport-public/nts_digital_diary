<?php

namespace App\Tests\Functional\DiaryKeeper;

use App\Entity\Interviewer;
use App\Entity\User;
use App\Entity\DiaryKeeper;
use App\Tests\DataFixtures\LoginTestFixtures;
use App\Tests\DataFixtures\TestSpecific\AccountActivationTestFixtures;
use App\Tests\Functional\AbstractFunctionalTestCase;
use App\Utility\AlphagovNotify\Reference;
use App\Utility\Security\AccountCreationHelper;

class AccountActivationTest extends AbstractFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->initialiseClientAndLoadFixtures([
            LoginTestFixtures::class,
            AccountActivationTestFixtures::class,
        ]);
    }

    public function testDiaryKeeperCanActivateAccountAndLogin(): void
    {
        $diaryKeeper = $this->fixtureReferenceRepository->getReference('diary-keeper:no-password', DiaryKeeper::class);

        $accountCreationHelper = self::getContainer()->get(AccountCreationHelper::class);
        $activationUrl = $accountCreationHelper->generateAccountCreationUrl($diaryKeeper);
        $activationUrl = str_replace('http://localhost', '', $activationUrl);

        $this->client->request('GET', $activationUrl);

        $this->completePreAuthForm();
        $this->setPassword('/travel-diary');
        $this->client->clickLink('Logout');
        $this->requestActivatedAccountUrlAndContinue($activationUrl);
    }

    protected function completePreAuthForm(?string $expectedUrlPath = null): void
    {
        $url = $expectedUrlPath ?? $this->client->getCurrentURL();
        $this->client->submitForm('form_continue', []);
        $this->assertEquals($url, $expectedUrlPath ? $this->getUrlPath() : $this->client->getCurrentURL());
    }

    protected function setPassword($expectedUrl): void
    {
        $newPassword = 'Str0ngP@ssw0rd!';
        $this->client->submitForm('change_password_button_group_save', [
            'change_password[password1]' => $newPassword,
            'change_password[password2]' => $newPassword,
        ]);

        $this->assertEquals($expectedUrl, $this->getUrlPath());
    }

    protected function requestActivatedAccountUrlAndContinue(string $url): void
    {
        $this->client->request('GET', $url);
        $this->client->submitForm('form_continue');

        $this->assertEquals('/travel-diary', $this->getUrlPath());
    }

    public function testUnactivatedDiaryKeeperFlowWithExpiredLink(): void
    {
        $diaryKeeper = $this->fixtureReferenceRepository->getReference('diary-keeper:no-password', DiaryKeeper::class);

        $accountCreationHelper = self::getContainer()->get(AccountCreationHelper::class);
        $activationUrl = $accountCreationHelper->generateAccountCreationUrl($diaryKeeper, currentTime: 0);
        $activationUrl = str_replace('http://localhost', '', $activationUrl);
        $this->client->request('GET', $activationUrl);
        $this->completePreAuthForm();
        $this->assertStringContainsString('The link you followed has expired', $this->client->getCrawler()->getText());

        $url = $this->getUrlFromMessage(Reference::ACCOUNT_CREATION_EVENT);
        $this->client->request('GET', $url);
        $this->completePreAuthForm();
        $this->assertStringContainsString('Create account', $this->client->getCrawler()->getText());
    }

    public function testActivatedDiaryKeeperFlowWithExpiredLink(): void
    {
        $diaryKeeper = $this->fixtureReferenceRepository->getReference('diary-keeper:adult', DiaryKeeper::class);

        $accountCreationHelper = self::getContainer()->get(AccountCreationHelper::class);
        $activationUrl = $accountCreationHelper->generateAccountCreationUrl($diaryKeeper, currentTime: 0);
        $activationUrl = str_replace('http://localhost', '', $activationUrl);
        $this->client->request('GET', $activationUrl);
        $this->completePreAuthForm();

        // Activated diary keepers are steered to log in rather than being resent a setup link.
        $pageText = $this->client->getCrawler()->getText();
        $this->assertStringContainsString('The link you followed has expired', $pageText);
        $this->assertStringContainsString('already have an account', $pageText);
    }


    public function testInterviewerCanActivateAccountAndLogin(): void
    {
        $interviewer = $this->fixtureReferenceRepository->getReference('interviewer:no-password', Interviewer::class);

        $accountCreationHelper = self::getContainer()->get(AccountCreationHelper::class);
        $activationUrl = $accountCreationHelper->generateAccountCreationUrl($interviewer);
        $activationUrl = str_replace('http://localhost', '', $activationUrl);

        $this->client->request('GET', $activationUrl);

        $this->completePreAuthForm();
        $this->setPassword('/login');
        $this->loginUser('interviewer-no-password@example.com', 'Str0ngP@ssw0rd!');
        $this->assertEquals('/interviewer', $this->getUrlPath());
        $this->client->clickLink('Logout');

        $this->client->request('GET', $activationUrl);
        $this->completePreAuthForm('/login');
        $this->assertEquals('/login', $this->getUrlPath());
    }
}
