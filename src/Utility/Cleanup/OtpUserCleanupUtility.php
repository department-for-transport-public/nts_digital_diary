<?php

namespace App\Utility\Cleanup;

use App\Entity\AreaPeriod;
use App\Entity\OtpUser;
use App\Repository\OtpUserRepository;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class OtpUserCleanupUtility
{
    public function __construct(protected EntityManagerInterface $entityManager, protected OtpUserRepository $otpUserRepository)
    {
    }

    public function removeOldOtpUsers(): int
    {
        $count = 0;

        /** @param array<OtpUser> $users */
        $removeUsers = function(array $users) use (&$count) : void {
            foreach($users as $user) {
                $this->entityManager->remove($user);
                $count++;
            }
        };

        // Examples:
        // * 2023/06/01 (Today) -> 2023/02/01 (4 months ago) -> Users with area periods before 2023/02
        // * 2023/06/30 (Today) -> 2023/02/01 (4 months ago) -> Users with area periods before 2023/02
        //   (= remove users belonging to every AreaPeriod up to and including 2023/01)

        // Chosen because the validation rules on Household allow a diaryWeekStartDate that starts:
        // a) >= first of the month, midnight
        // b) <  three months later than that first date

        // e.g. Area period 2023/01 gives 2023/01/01 >= diaryWeekStartDate < 2023/04/01

        $fourMonthsAgo = (new DateTime("first day of " . AreaPeriod::VALID_FIELDWORK_PERIOD_MONTHS . " months ago"))
            ->sub(DateInterval::createFromDateString('1 month'));
        $removeUsers($this->otpUserRepository->findUsersWithAreaPeriodBefore(
            intval($fourMonthsAgo->format('Y')),
            intval($fourMonthsAgo->format('m'))
        ));

        $sevenDaysAgo = new DateTime('7 days ago');
        $removeUsers($this->otpUserRepository->findUsersOnboardedBefore($sevenDaysAgo));

        $this->entityManager->flush();

        return $count;
    }
}