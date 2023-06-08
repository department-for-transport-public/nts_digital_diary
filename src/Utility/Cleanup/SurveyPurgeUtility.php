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


        $households = $this->householdRepository->getSubmittedSurveysForPurge($twoHundredDaysAgo);

        foreach($households as $household) {
            $this->entityManager->remove($household);
            $count++;
        }

        if ($count > 0) {
            $this->entityManager->flush();
        }

        return $count;
    }
}