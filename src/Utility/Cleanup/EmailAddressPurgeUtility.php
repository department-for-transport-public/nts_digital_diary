<?php

namespace App\Utility\Cleanup;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class EmailAddressPurgeUtility
{
    public function __construct(protected EntityManagerInterface $entityManager, protected UserRepository $userRepository)
    {}

    public function purgeOldEmailAddresses(): int
    {
        $count = 0;

        $sixtyDaysAgo = new \DateTime('60 days ago');
        $now = new \DateTime();

        $users = $this->userRepository->getUsersForEmailPurge($sixtyDaysAgo);

        foreach($users as $user) {
            $user
                ->setUserIdentifier(User::generateNoLoginPlaceholder())
                ->setEmailPurgeDate($now)
                ->setPassword(null)
                ->setPasswordResetCode(null);

            $count++;
        }

        if ($count > 0) {
            $this->entityManager->flush();
        }

        return $count;
    }
}