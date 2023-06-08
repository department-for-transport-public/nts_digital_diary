<?php

namespace App\Tests\DataFixtures\TestSpecific;

use App\DataFixtures\NtsFixtures;
use App\Entity\DiaryKeeper;
use App\Entity\Household;
use App\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class DiaryKeeperForDuplicateUsernameFixtures extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var Household $household */
        $household = $this->getReference('household:not-onboarded');

        $dk1 = (new DiaryKeeper())
            ->setName('Charlie')
            ->setNumber(1)
            ->setIsAdult(true)
            ->setMediaType(DiaryKeeper::MEDIA_TYPE_DIGITAL)
            ->setUser($this->createUser($manager, 'diary-keeper-duplicate@example.com', 'duplicate'));
            ;

        $this->addReference('diary-keeper:duplicate', $dk1);

        $household->addDiaryKeeper($dk1);

        $manager->persist($dk1);
        $manager->flush();
    }

    protected function createUser(ObjectManager $manager, string $email, ?string $password='password'): User
    {
        $user = (new User())
            ->setUsername($email);

        if ($password) {
            $user->setPlainPassword($password);
        }

        $manager->persist($user);
        return $user;
    }

    public function getDependencies(): array
    {
        return [NtsFixtures::class];
    }
}