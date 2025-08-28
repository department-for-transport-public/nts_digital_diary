<?php

namespace App\Tests\Functional\Auth;

use App\Entity\DiaryKeeper;
use App\Utility\AlphagovNotify\Reference;
use App\Utility\Security\AccountCreationHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class AccountCreationLinkExpiredTest extends AbstractAuthTestCase
{
    protected AccountCreationHelper $accountCreationHelper;
    public function setUp(): void
    {
        parent::setUp();
        $this->accountCreationHelper = static::getContainer()->get(AccountCreationHelper::class);
    }

    public function dataAccountCreationLinkExpired(): array
    {
        return [
            [null],
            ['new.address@example.com'],
        ];
    }

    /**
     * @dataProvider dataAccountCreationLinkExpired
     */
    public function testAccountCreationLinkExpired(?string $emailOverride): void
    {
        $diaryKeeper = $this->fixtureReferenceRepository->getReference('diary-keeper:no-password', DiaryKeeper::class);

        $originalLink = $this->accountCreationHelper->generateAccountCreationUrl(
            $diaryKeeper, $emailOverride, time() - Reference::ACCOUNT_CREATION_LINK_EXPIRY
        );

        $this->client->request(Request::METHOD_GET, $originalLink);

        // get the resent link
        $expectedRecipient = $emailOverride ?? $diaryKeeper->getUser()->getUserIdentifier();
        $resentLink = $this->getUrlFromMessage(Reference::ACCOUNT_CREATION_EVENT);
        $request = Request::create($resentLink);
        self::assertEquals($emailOverride, $request->query->get(AccountCreationHelper::EMAIL_OVERRIDE_PARAM));
        self::assertEquals($expectedRecipient, $this->getAddresseeFromMessage(Reference::ACCOUNT_CREATION_EVENT));
        self::assertSelectorTextContains('#main-content', $expectedRecipient);

        $this->clearMessages();

        // ensure that if we visit the original link again, that a second email isn't sent
        $this->client->request(Request::METHOD_GET, $originalLink);
        $this->assertNoMessages();

        // follow resent link, and ensure it's valid
        $this->client->request(Request::METHOD_GET, $resentLink);
        self::assertSelectorTextSame('h1', 'Set password for Travel Diary account');
    }

    protected function clearMessages(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $entityManager->getConnection()->executeQuery("DELETE FROM messenger_messages WHERE 'something-to-prevent-delete-warning'=?", ['something-to-prevent-delete-warning']);
        $entityManager->clear();
    }

    protected function assertNoMessages(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $message = $entityManager->getConnection()->fetchAssociative('SELECT * FROM messenger_messages LIMIT 1');
        self::assertFalse($message);
    }
}
