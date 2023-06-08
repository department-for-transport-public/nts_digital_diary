<?php

namespace App\Security\OneTimePassword;

use App\Entity\OtpUser;
use App\Repository\OtpUserRepository;
use Exception;
use RuntimeException;

class PasscodeGenerator
{
    protected ?OtpUserRepository $otpUserRepository;
    protected string $secret;

    public function __construct(?OtpUserRepository $otpUserRepository, string $secret)
    {
        $this->otpUserRepository = $otpUserRepository;
        $this->secret = $secret;
    }

    public function createNewPasscodeUser(): OtpUser
    {
        return (new OtpUser())
            ->setUserIdentifier($this->generateUserIdentifierCode());
    }

    public function getPasswordForUserIdentifier(?string $userIdentifier): string
    {
        if (is_null($userIdentifier)) {
            throw new RuntimeException("User identifier cannot be null");
        }
        $hash = hash('sha256', $this->secret.$userIdentifier);
        $passcode = hexdec(substr($hash, -8, 8));
        return str_pad($passcode % 100000000, 8, '0', STR_PAD_LEFT);
    }

    public function generateUserIdentifierCode(): string
    {
        while(($passcode = $this->checkPasscode($this->generateUntestedCode(), true)) === null) {continue;};
        return $passcode;
    }

    protected function generateUntestedCode(): string
    {
        try {
            $number = random_int(1, 99999999);
        } catch (Exception $e) {
            throw new RuntimeException("Unable to generate random number", 0, $e);
        }

        return str_pad($number, 8, '0', STR_PAD_LEFT);
    }

    protected function checkPasscode($passcode, $enforceUniqueUsername = false): ?string
    {
        if (!$this->isValidPasscode($passcode)) {
            return null;
        }

        if ($enforceUniqueUsername) {
            if ($this->usernameExistsInDatabase($passcode)) {
                return null;
            }
        }

        return $passcode;
    }

    public function isValidPasscode($passcode, $preventRepeating = 4, $preventSequential = 4): bool
    {
        if (strlen($passcode) !== 8) {
            return false;
        }

        // reject if 4 consecutive numbers same
        if ($this->hasRepeatingDigits($passcode, $preventRepeating)) {
            return false;
        }

        // test for sequential
        if ($this->hasSequentialDigits($passcode, $preventSequential)) {
            return false;
        }

        return true;
    }

    protected function usernameExistsInDatabase($passcode): bool
    {
        // TODO: also need to check entity manager for as yet un-persisted new users
        $result = $this->otpUserRepository->findBy(['userIdentifier' => $passcode]);
        return (!empty($result));
    }

    protected function hasRepeatingDigits($passcode, $preventRepeating = 4): bool
    {
        return preg_match('/(\d)\1{' . ($preventRepeating - 1) . '}/', $passcode) === 1;
    }

    protected function hasSequentialDigits($passcode, $sequentialCount = 4): bool
    {
        $positionLimit = strlen($passcode) - $sequentialCount;
        if ($positionLimit < 0) {
            return false;
        }

        for($i=0; $i<=$positionLimit; $i++) {
            $number = substr($passcode, $i, $sequentialCount);
            $up = $down = true;
            $current = intval($number[0]);

            for($j=1; $j<$sequentialCount; $j++) {
                $next = intval($number[$j]);
                $up &= ($next === ($current + 1) % 10);
                $down &= ($next === ($current - 1) % 10);
                $current = $next;
            }

            if ($up || $down) {
                return true;
            }
        }

        return false;
    }
}