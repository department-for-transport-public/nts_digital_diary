<?php

namespace App\Tests\Repository;

use App\Repository\UserRepository;
use App\Tests\DataFixtures\TestSpecific\UserRepositoryTestFixtures;

class UserRepositoryTest extends AbstractRepositoryTest
{
    protected UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->bootKernelAndLoadFixtures([UserRepositoryTestFixtures::class]);
        $this->userRepository = self::getContainer()->get(UserRepository::class);
    }

    public function dataIsExistingUserWithEmailAddress(): array
    {
        return [
            [true,  'user@example.com', null],
            [false, 'user@example.com', 'user:user'],
            [false, 'user-with-training-interviewer@example.com', null],
            [false, 'user-with-training-interviewer@example.com', 'user:user-with-training-interviewer'],
        ];
    }

    /**
     * @dataProvider dataIsExistingUserWithEmailAddress
     */
    public function testIsExistingUserWithEmailAddress(bool $expectedResult, string $email, ?string $userReferenceForExcludingUserId): void
    {
        $excludingUserId = $userReferenceForExcludingUserId ?
            $this->fixtureReferenceRepository->getReference($userReferenceForExcludingUserId)->getId() :
            null;

        $this->assertEquals($expectedResult, $this->userRepository->canChangeEmailTo($email, $excludingUserId));
    }
}