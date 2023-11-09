<?php

namespace App\Tests\DataFixtures\TestSpecific;

use App\Entity\Interviewer;
use App\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

class UserRepositoryTestFixtures extends AbstractFixture
{
    public function load(ObjectManager $manager): void
    {
        $interviewerUser = $this->createUser('interviewer@example.com');
        $this->addReference('user:interviewer', $interviewerUser);
        $interviewer = (new Interviewer())
            ->setUser($interviewerUser)
            ->setName('Interviewer')
            ->setSerialId('1');
        
        $userOne = $this->createUser('user@example.com');
        $this->addReference('user:user', $userOne);

        $userTwo = $this->createUser('user-with-training-interviewer@example.com', $interviewer);
        $this->addReference('user:user-with-training-interviewer', $userTwo);

        foreach([$interviewerUser, $interviewer, $userOne, $userTwo] as $entity) {
            $manager->persist($entity);
        }

        $manager->flush();
    }

    protected function createUser(string $username, ?Interviewer $trainingInterviewer=null): User
    {
        return (new User())
            ->setUsername($username)
            ->setTrainingInterviewer($trainingInterviewer);
    }
}