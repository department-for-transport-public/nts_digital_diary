<?php

namespace App\Utility\Cleanup;

use App\Entity\Household;
use App\Repository\HouseholdRepository;
use App\Repository\PropertyChangeLogRepository;
use Doctrine\ORM\EntityManagerInterface;

class SurveyPurgeUtility
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected HouseholdRepository $householdRepository,
        protected PropertyChangeLogRepository $propertyChangeLogRepository,
    ) {}

    public function purgeOldSurveys(): int
    {
        $count = 0;
        $twoHundredDaysAgo = new \DateTime('200 days ago');
        $households = $this->householdRepository->getSurveysForPurge($twoHundredDaysAgo);

        foreach($households as $household) {
            $this->deletePropertyChangeLogsForHousehold($household);

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

    protected function deletePropertyChangeLogsForHousehold(Household $household): void
    {
        $entityIds = [];

        foreach ($household->getDiaryKeepers() as $diaryKeeper) {
            foreach ($diaryKeeper->getDiaryDays() as $diaryDay) {
                foreach ($diaryDay->getJourneys() as $journey) {
                    $entityIds[] = $journey->getId();
                    foreach ($journey->getStages() as $stage) {
                        $entityIds[] = $stage->getId();
                    }
                }
            }
        }

        $this->propertyChangeLogRepository->deleteForEntityIds($entityIds);
    }
}