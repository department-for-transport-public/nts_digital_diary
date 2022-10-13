<?php

namespace App\Tests\Functional\OnBoarding;

use App\Security\OneTimePassword\PasscodeGenerator;
use App\Tests\DataFixtures\OtpUserFixtures;
use App\Tests\Functional\AbstractWizardTest;

abstract class AbstractOtpTest extends AbstractWizardTest
{
    protected PasscodeGenerator $passcodeGenerator;

    public function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([
            OtpUserFixtures::class
        ]);

        $this->passcodeGenerator = self::getContainer()->get(PasscodeGenerator::class);
    }
}