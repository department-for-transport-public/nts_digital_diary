<?php

namespace App\Tests\Security\OneTimePassword;

use App\Repository\OtpUserRepository;
use App\Security\OneTimePassword\PasscodeGenerator;
use PHPUnit\Framework\TestCase;

class PasswordGeneratorTest extends TestCase
{
    public function dataIsValidPasscode(): array
    {
        return [
            ['12376590', true],  // Valid: 8 characters with no sequences of 4+ digits
            ['11122233', true],  // Valid: 8 characters with no repeats of 4+ digits

            ['9123748',  false], // Invalid: Not 8 characters
            ['91234883', false], // Invalid: Sequence of 4 digits
            ['91111883', false], // Invalid: Repeat of 4 digits
            ['94321883', false], // Invalid: Reverse sequence of 4 digits
        ];
    }

    /**
     * @dataProvider dataIsValidPasscode
     */
    public function testIsValidPasscode(string $passcode, bool $expectedToBeValid): void
    {
        $otpUserRepository = $this->createMock(OtpUserRepository::class);
        $generator = new PasscodeGenerator($otpUserRepository, 'abcde');

        $isValid = $generator->isValidPasscode($passcode);

        $this->assertEquals($expectedToBeValid, $isValid);
    }
}