<?php

namespace App\Tests\DataFixtures\TestSpecific;

use App\DataFixtures\NtsFixtures;
use App\Entity\Interviewer;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class AccountActivationTestFixtures extends AbstractFixture implements DependentFixtureInterface
{
    use CreateUserTrait;

    public function load(ObjectManager $manager): void
    {
        $interviewerNoPassword = (new Interviewer())
            ->setName('Test interviewer (No password set)')
            ->setSerialId(102)
            ->setUser($this->createUser($manager, 'interviewer-no-password@example.com', null)->setHasLoggedIn(true));;
        $manager->persist($interviewerNoPassword);
        $this->setReference('interviewer:no-password', $interviewerNoPassword);
        $this->setReference('user:interviewer:no-password', $interviewerNoPassword->getUser());

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [NtsFixtures::class];
    }
}
