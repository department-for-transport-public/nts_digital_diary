<?php

namespace App\Tests\DataFixtures\TestSpecific;

use App\Entity\User;
use Doctrine\Persistence\ObjectManager;

trait CreateUserTrait
{
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
}