<?php

namespace App\Utility\Cleanup;

use App\Entity\OtpUser;
use App\Repository\OtpUserRepository;
use Doctrine\ORM\EntityManagerInterface;

class OtpUserCleanupUtility
{
    protected EntityManagerInterface $entityManager;
    protected OtpUserRepository $otpUserRepository;

    public function __construct(EntityManagerInterface $entityManager, OtpUserRepository $otpUserRepository)
    {
        $this->entityManager = $entityManager;
        $this->otpUserRepository = $otpUserRepository;
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
        // * 2023/04/01 (Today) -> 2023/02/01 (2 months ago) -> Users with area periods before 2023/02
        // * 2023/04/30 (Today) -> 2023/02/01 (2 months ago) -> Users with area periods before 2023/02
        //   (= remove users belonging to every AreaPeriod up to and including 2023/01)

        // Chosen because the validation rules on Household allow a diaryWeekStartDate that starts:
        // a) >= first of the month, midnight
        // b) <  two months later than that first date

        // e.g. Area period 2023/01 gives 2023/01/01 >= diaryWeekStartDate < 2023/03/01

        $oneMonthAgo = (new \DateTime("midnight, first day of 2 months ago"));
        $removeUsers($this->otpUserRepository->findUsersWithAreaPeriodBefore(
            intval($oneMonthAgo->format('Y')),
            intval($oneMonthAgo->format('m'))
        ));

        $sevenDaysAgo = new \DateTime('7 days ago');
        $removeUsers($this->otpUserRepository->findUsersOnboardedBefore($sevenDaysAgo));

        $this->entityManager->flush();

        return $count;
    }
}