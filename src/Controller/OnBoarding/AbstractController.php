<?php

namespace App\Controller\OnBoarding;

use App\Controller\AbstractController as RootAbstractController;
use App\Entity\Household;
use App\Security\OneTimePassword\OtpUserInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AbstractController extends RootAbstractController
{
    protected function checkIsCorrectHousehold(Household $household)
    {
        $user = $this->getUser();

        if (!$user instanceof OtpUserInterface || $user->getHousehold() !== $household) {
            throw new AccessDeniedHttpException('Not a member of the expected household');
        }
    }
}