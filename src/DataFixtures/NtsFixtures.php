<?php

namespace App\DataFixtures;

use App\Entity\Journey\Method;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class NtsFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $this->persistEntitiesIfNotExists($manager, $this->getMethodFixtures());
        $manager->flush();
    }

    /**
     * @return array | Method[]
     */
    protected function getMethodFixtures(): array
    {
        return [
            Method::method(1, 1, 'walk', 'other', 'private', 1),
            Method::method(2, 4, 'car', 'private', 'private', 2),
            Method::method(12, 2, 'bicycle', 'other', 'private', 3),
            Method::method(3, 5, 'motorcycle', 'private', 'private', 4),
            Method::method(4, 6, 'van-or-lorry', 'private', 'private', 5),
            Method::method(13, 21, 'e-bike', 'other', 'private', 6),
            Method::method(14, 22, 'e-scooter', 'other', 'private', 7),
            Method::method(15, 23, 'mobility-scooter', 'other', 'private', 8),

            Method::method(5, null, 'other-private', 'private', 'private', 9),

            Method::method(6, null, 'bus-or-coach', 'public', 'public', 10),
            Method::method(7, 13, 'train', 'public', 'public', 11),
            Method::method(16, 14, 'light-rail', 'public', 'public', 12),
            Method::method(8, 12, 'london-underground', 'public', 'public', 13),
            Method::method(9, 16, 'taxi', 'other', 'public', 14),
            Method::method(17, 24, 'ferry', 'public', 'public', 15),
            Method::method(10, null, 'other-public', 'public', 'public', 16),
        ];
    }

    protected function persistEntitiesIfNotExists(ObjectManager $manager, array $entities) {
        foreach($entities as $entity) {
            if (!method_exists($entity, 'getId')) {
                throw new \RuntimeException('Entity is missing getId method');
            }

            if ($manager->find(get_class($entity), $entity->getId()) === null) {
                $manager->persist($entity);
            }
        }
    }
}
