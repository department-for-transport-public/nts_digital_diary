<?php

namespace App\Tests\DataFixtures;

use App\Entity\ApiUser;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

class ApiUserFixtures extends AbstractFixture
{
    public function load(ObjectManager $manager)
    {
        $apiUser = (new ApiUser())->setKey('test-key')->setNonce(time());
        $manager->persist($apiUser);
        $manager->flush();
        $this->addReference('api-user:1', $apiUser);
    }
}