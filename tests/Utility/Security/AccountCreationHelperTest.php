<?php

namespace App\Tests\Utility\Security;

use App\Entity\DiaryKeeper;
use App\Entity\Household;
use App\Entity\Interviewer;
use App\Utility\AlphagovNotify\Reference;
use App\Utility\DateTimeFormats;
use App\Utility\Security\AccountCreationHelper;
use App\Utility\Security\UrlSigner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\RouterInterface;

class AccountCreationHelperTest extends TestCase
{
    private function helper(): AccountCreationHelper
    {
        return new AccountCreationHelper(
            $this->createMock(MessageBusInterface::class),
            $this->createMock(RouterInterface::class),
            $this->createMock(UrlSigner::class),
            $this->createMock(DateTimeFormats::class),
        );
    }

    private function diaryKeeperWithWeekStart(?\DateTime $weekStart): DiaryKeeper
    {
        return (new DiaryKeeper())
            ->setHousehold((new Household())->setDiaryWeekStartDate($weekStart));
    }

    public function testDiaryKeeperExpiryIsAnchoredToWeekEndPlusGrace(): void
    {
        $now = (new \DateTime('2026-01-01'))->getTimestamp();
        $weekStart = new \DateTime('2026-01-10');

        $expected = (clone $weekStart)->modify('+7 days')->getTimestamp()
            + Reference::ACCOUNT_CREATION_LINK_GRACE
            - $now;

        $this->assertSame(
            $expected,
            $this->helper()->calculateLinkExpiry($this->diaryKeeperWithWeekStart($weekStart), $now)
        );
    }

    public function testDiaryKeeperExpiryIsFlooredWhenSentAfterTheWeekEnds(): void
    {
        $weekStart = new \DateTime('2026-01-10');
        $now = (clone $weekStart)->modify('+20 days')->getTimestamp();

        $this->assertSame(
            Reference::ACCOUNT_CREATION_LINK_MIN_EXPIRY,
            $this->helper()->calculateLinkExpiry($this->diaryKeeperWithWeekStart($weekStart), $now)
        );
    }

    public function testDiaryKeeperWithoutWeekDateUsesFixedFallback(): void
    {
        $now = (new \DateTime('2026-01-01'))->getTimestamp();

        $this->assertSame(
            Reference::ACCOUNT_CREATION_LINK_EXPIRY,
            $this->helper()->calculateLinkExpiry($this->diaryKeeperWithWeekStart(null), $now)
        );
    }

    public function testInterviewerUsesFixedFallback(): void
    {
        $now = (new \DateTime('2026-01-01'))->getTimestamp();

        $this->assertSame(
            Reference::ACCOUNT_CREATION_LINK_EXPIRY,
            $this->helper()->calculateLinkExpiry(new Interviewer(), $now)
        );
    }
}
