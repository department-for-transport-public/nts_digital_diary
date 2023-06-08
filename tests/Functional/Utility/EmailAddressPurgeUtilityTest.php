<?php

namespace App\Tests\Functional\Utility;

use App\Entity\User;

class EmailAddressPurgeUtilityTest extends AbstractPurgeTest
{
    public function dataPrePurge(): array
    {
        return [
            ['now', false],
            ['59-days-ago', false],
            ['61-days-ago', false],
            ['300-days-ago', false],
        ];
    }

    /** @dataProvider dataPrePurge */
    public function testPrePurge(string $dateString, bool $expectedToBeWiped): void {
        $this->assertUserEmailWipeStatus($dateString, $expectedToBeWiped);
    }

    public function dataPostPurge(): array
    {
        return [
            ['now', false],
            ['59-days-ago', false],
            ['61-days-ago', true],
            ['300-days-ago', true],
        ];
    }

    /** @dataProvider dataPostPurge */
    public function testPostPurge(string $dateString, bool $expectedToBeWiped): void {
        $this->addressPurgeUtility->purgeOldEmailAddresses();
        $this->assertUserEmailWipeStatus($dateString, $expectedToBeWiped);
    }

    protected function assertUserEmailWipeStatus(string $dateString, bool $expectedToBeWiped): void
    {
        /** @var User $user */
        $user = $this->getFixtureByReference("surveys:{$dateString}:user");

        if ($expectedToBeWiped) {
            $this->assertStringStartsWith(User::NO_LOGIN_PLACEHOLDER . ':', $user->getUserIdentifier());
            $this->assertNotEquals(null, $user->getEmailPurgeDate());
            $this->assertEquals(null, $user->getPassword());
            $this->assertEquals(null, $user->getPasswordResetCode());
        } else {
            // Can't make a determination on the user identifier - could be a proxy - in which case no-login: is valid
            $this->assertEquals(null, $user->getEmailPurgeDate());
            $this->assertNotEquals(null, $user->getPassword());
        }
    }
}