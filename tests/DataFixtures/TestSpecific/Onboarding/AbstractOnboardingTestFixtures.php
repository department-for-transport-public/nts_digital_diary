<?php

namespace App\Tests\DataFixtures\TestSpecific\Onboarding;

use App\Entity\DiaryKeeper;
use App\Entity\Household;
use App\Entity\Interviewer;
use App\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

abstract class AbstractOnboardingTestFixtures extends AbstractFixture
{
    protected ObjectManager $manager;

    protected function createDiaryKeeper(Household $household, string $name, ?string $email, int $number, bool $isAdult): DiaryKeeper
    {
        $user = (new User())
            ->setUserIdentifier($email ?? User::generateNoLoginPlaceholder());

        $diaryKeeper = (new DiaryKeeper())
            ->setName($name)
            ->setNumber($number)
            ->setUser($user)
            ->setIsAdult($isAdult)
            ->setMediaType(DiaryKeeper::MEDIA_TYPE_DIGITAL); // DiaryKeeperDataMapper initially sets this

        $serial = $household->getSerialNumber($number, true, false);
        $this->setReference("diary-keeper:{$serial}", $diaryKeeper);

        $household->addDiaryKeeper($diaryKeeper);

        $this->manager->persist($diaryKeeper);
        $this->manager->persist($user);

        return $diaryKeeper;
    }

    protected function createInterviewer(string $name, string $email, string $serialId): Interviewer
    {
        $user = (new User())
            ->setUserIdentifier($email);

        $interviewer = (new Interviewer())
            ->setName($name)
            ->setUser($user)
            ->setSerialId($serialId);

        $this->setReference("interviewer:{$serialId}", $interviewer);

        $this->manager->persist($interviewer);
        $this->manager->persist($user);

        return $interviewer;
    }

    protected function createTrainingUser(string $email, Interviewer $trainingInterviewer): User
    {
        $user = (new User())
            ->setUserIdentifier($email)
            ->setTrainingInterviewer($trainingInterviewer);

        $this->manager->persist($user);

        return $user;
    }
}
