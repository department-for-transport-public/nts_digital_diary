<?php

namespace App\Tests\DataFixtures;

use App\Entity\DiaryKeeper;
use App\Entity\Household;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ApiFixtures extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $dkRepo = $manager->getRepository(DiaryKeeper::class);
        $householdRepo  = $manager->getRepository(Household::class);

        $dkRepo->createQueryBuilder('dk')
            ->update()
            ->set('dk.diaryState', ':state')
            ->setParameter('state', DiaryKeeper::STATE_APPROVED)
            ->getQuery()
            ->execute();
        $householdRepo->createQueryBuilder('h')
            ->update()
            ->set('h.submittedAt', ':subAt')
            ->set('h.submittedBy', ':subBy')
            ->setParameters([
                'subAt' => new \DateTime('2021-11-22 13:30'),
                'subBy' => 'T1000',
            ])
            ->getQuery()
            ->execute();
    }

    public function getDependencies(): array
    {
        return [StageFixtures::class];
    }
}

