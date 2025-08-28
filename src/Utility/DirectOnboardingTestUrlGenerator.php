<?php

namespace App\Utility;

use ApiPlatform\Metadata\UrlGeneratorInterface;
use App\Repository\HouseholdRepository;
use App\Repository\OtpUserRepository;
use App\Security\OneTimePassword\OtpUserInterface;
use App\Security\OneTimePassword\PasscodeGenerator;
use App\Utility\TravelDiary\SerialHelper;
use DateInterval;

class DirectOnboardingTestUrlGenerator
{
    public function __construct(
        protected HouseholdRepository   $householdRepository,
        protected PasscodeGenerator     $passcodeGenerator,
        protected UrlGeneratorInterface $router,
        protected OtpUserRepository     $otpUserRepository,
    ) {}

    public function generateUrl(OtpUserInterface    |string $user, int $addressNumber, int $diaryKeeperCount = 4, int $vehicleCount = 2, string $urlGenerationMode = UrlGeneratorInterface::ABS_URL): string
    {
        if (is_string($user) || is_numeric($user)) {
            $user = $this->otpUserRepository->loadUserByIdentifier($user);
        }
        $diaryKeepers = [
            1 => ['name' => 'Alice', 'adult' => 1],
            2 => ['name' => 'Bob', 'adult' => 1],
            3 => ['name' => 'Charlie', 'adult' => 0],
            4 => ['name' => 'Daisy', 'adult' => 0],
        ];
        $diaryKeepers = array_slice($diaryKeepers, 0, min($diaryKeeperCount, 4), true);

        $vehicles = [
            1 => ['name' => 'Red Mazda', 'method' => 4, 'driver' => 1],
            2 => ['name' => 'Grey VW', 'method' => 4, 'driver' => 2],
        ];
        $vehicles = array_slice($vehicles, 0, min($diaryKeeperCount, $vehicleCount, 2), true);

        return $this->router->generate('onboarding_direct_auth_index', [
                'passcode1' => $user->getUserIdentifier(),
                'passcode2' => $this->passcodeGenerator->getPasswordForUserIdentifier($user->getUserIdentifier()),
                'cl' => SerialHelper::getCheckLetter($user->getAreaPeriod()->getArea(), $addressNumber, 1),
                'add' => $addressNumber, 'h' => 1,
                'start' => ($user->getAreaPeriod()->getFirstValidDiaryStartDate())->add(DateInterval::createFromDateString('45 days'))->format('Y-m-d'),
                'dk' => $diaryKeepers,
                'v' => $vehicles
            ], $urlGenerationMode);
    }
}
