<?php

namespace App\Utility\TravelDiary;

use App\Entity\AreaPeriod;
use App\Entity\OtpUser;
use App\Security\OneTimePassword\PasscodeGenerator;
use Doctrine\ORM\EntityManagerInterface;

class AreaPeriodHelper
{
    const CODES_PER_AREA = 22;

    private PasscodeGenerator $passcodeGenerator;
    private EntityManagerInterface $entityManager;

    public function __construct(PasscodeGenerator $passcodeGenerator, EntityManagerInterface $entityManager)
    {
        $this->passcodeGenerator = $passcodeGenerator;
        $this->entityManager = $entityManager;
    }

    /**
     * @return array<OtpUser> $areaPeriod
     */
    public function createCodesForArea(AreaPeriod $areaPeriod, $flush = true): array
    {
        $otpUsers = [];
        for($i=0; $i<self::CODES_PER_AREA; $i++) {
            $otpUser = $this->passcodeGenerator
                ->createNewPasscodeUser();
            $areaPeriod->addOtpUser($otpUser);

            $this->entityManager->persist($otpUser);
            $otpUsers[] = $otpUser;
        }

        if ($flush) $this->entityManager->flush();
        return $otpUsers;
    }

    /**
     * Guesses the year based on the area number - up to 2 years in advance, and 7 years in the past
     */
    public static function guessYearFromArea(string $area): int
    {
        $currentYear = (new \DateTime())->format('Y');
        $currentYearDigit = $currentYear[3];
        $areaYearDigit = $area[0];
        $areaYear = $currentYear;
        $areaYear[3] = $areaYearDigit;
        if ($areaYearDigit > ($currentYearDigit + 2)) {
            $areaYear -= 10;
        }
        return intval($areaYear);
    }
}