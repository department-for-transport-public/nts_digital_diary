<?php

namespace App\Tests\Utility;

use App\Entity\User;
use App\Utility\DateTimeFormats;
use App\Utility\TravelDiary\UserPreferencesHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\UserInterface;

class UserPreferencesHelperTest extends TestCase
{
    public function dataHasUserPreferences(): array
    {
        return [
            [false, $this->getNormalToken($this->getNonPrefsUser())],
            [false, $this->getSwitchedUserToken($this->getPrefsUser(null), $this->getNonPrefsUser())],
            [true, $this->getNormalToken($this->getPrefsUser(null))],
            [true, $this->getSwitchedUserToken($this->getNonPrefsUser(), $this->getPrefsUser(null))],
        ];
    }

    /**
     * @dataProvider dataHasUserPreferences
     */
    public function testHasUserPreferences(bool $expectedToHaveUserPreferences, TokenInterface $token): void
    {
        $helper = $this->getUserPreferencesHelper($token);
        $this->assertEquals($expectedToHaveUserPreferences, $helper->hasUserPreferences());
    }

    public function dataGetOriginalUserPreferences(): array
    {
        $mode12 = DateTimeFormats::MODE_12_HOUR;
        $mode24 = DateTimeFormats::MODE_24_HOUR;

        return [
            [null, $this->getNormalToken($this->getNonPrefsUser())],
            [$mode12, $this->getNormalToken($this->getPrefsUser(null))], // The default
            [$mode12, $this->getNormalToken($this->getPrefsUser($mode12))],
            [$mode24, $this->getNormalToken($this->getPrefsUser($mode24))],

            [null, $this->getSwitchedUserToken($this->getNonPrefsUser(), $this->getNonPrefsUser())],
            [$mode12, $this->getSwitchedUserToken($this->getNonPrefsUser(), $this->getPrefsUser(null))],
            [$mode12, $this->getSwitchedUserToken($this->getNonPrefsUser(), $this->getPrefsUser($mode12))],
            [$mode24, $this->getSwitchedUserToken($this->getNonPrefsUser(), $this->getPrefsUser($mode24))],

            [null, $this->getSwitchedUserToken($this->getPrefsUser(null), $this->getNonPrefsUser())],
            [$mode12, $this->getSwitchedUserToken($this->getPrefsUser(null), $this->getPrefsUser(null))],
            [$mode12, $this->getSwitchedUserToken($this->getPrefsUser(null), $this->getPrefsUser($mode12))],
            [$mode24, $this->getSwitchedUserToken($this->getPrefsUser(null), $this->getPrefsUser($mode24))],

            [null, $this->getSwitchedUserToken($this->getPrefsUser($mode12), $this->getNonPrefsUser())],
            [$mode12, $this->getSwitchedUserToken($this->getPrefsUser($mode12), $this->getPrefsUser(null))],
            [$mode12, $this->getSwitchedUserToken($this->getPrefsUser($mode12), $this->getPrefsUser($mode12))],
            [$mode24, $this->getSwitchedUserToken($this->getPrefsUser($mode12), $this->getPrefsUser($mode24))],

            [null, $this->getSwitchedUserToken($this->getPrefsUser($mode24), $this->getNonPrefsUser())],
            [$mode12, $this->getSwitchedUserToken($this->getPrefsUser($mode24), $this->getPrefsUser(null))],
            [$mode12, $this->getSwitchedUserToken($this->getPrefsUser($mode24), $this->getPrefsUser($mode12))],
            [$mode24, $this->getSwitchedUserToken($this->getPrefsUser($mode24), $this->getPrefsUser($mode24))],
        ];
    }

    /**
     * @dataProvider dataGetOriginalUserPreferences
     */
    public function testGetOriginalUserPreferences(?string $expectedTimeFormat, TokenInterface $token): void
    {
        $helper = $this->getUserPreferencesHelper($token);

        $preferences = $helper->getOriginalUserPreferences();

        if (!$expectedTimeFormat) {
            $this->assertNull($preferences);
        } else {
            $this->assertEquals($expectedTimeFormat, $preferences->getTimeFormat());
        }
    }

    protected function getNonPrefsUser(): UserInterface
    {
        return new InMemoryUser('wibble', 'wobble');
    }

    protected function getPrefsUser(?string $timeFormat): UserInterface
    {
        $user = new User();

        if ($timeFormat !== null) {
            $user->getPreferences()->setTimeFormat($timeFormat);
        }

        return $user;
    }

    protected function getNormalToken(UserInterface $user): UsernamePasswordToken
    {
        return new UsernamePasswordToken($user, 'main');
    }

    protected function getSwitchedUserToken(UserInterface $user, UserInterface $original): SwitchUserToken
    {
        return new SwitchUserToken($user, 'main', [], $this->getNormalToken($original));
    }

    protected function getUserPreferencesHelper(TokenInterface $token): UserPreferencesHelper
    {
        $securityMock = $this->createMock(Security::class);

        $securityMock
            ->method('getToken')
            ->willReturn($token);

        return new UserPreferencesHelper($securityMock);
    }
}
