<?php

namespace App\Utility\Cleanup;

use App\Repository\HouseholdRepository;
use Doctrine\ORM\EntityManagerInterface;

class SurveyPurgeUtility
{
    public function __construct(protected EntityManagerInterface $entityManager, protected HouseholdRepository $householdRepository)
    {}

    public function purgeOldSurveys(): int
    {
        $count = 0;
        $twoHundredDaysAgo = new \DateTime('200 days ago');
        $households = $this->householdRepository->getSurveysForPurge($twoHundredDaysAgo);

        foreach($households as $household) {
            $areaPeriod = $household->getAreaPeriod();
            $householdCount = $areaPeriod->getHouseholds()->count();
            $this->entityManager->remove($household);
            $areaPeriod->getHouseholds()->removeElement($household);
            $count++;

            if ($householdCount === 1) {
                $this->entityManager->remove($areaPeriod);
            }
        }

        if ($count > 0) {
            $this->entityManager->flush();
        }

        return $count;
    }
}