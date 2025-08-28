<?php

namespace App\Security\OneTimePassword;

use App\Entity\AreaPeriod;
use App\Entity\Interviewer;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class DirectOnboardingTrainingUserProvider implements UserProviderInterface
{
    public const USER_IDENTIFIER = "87654321";

    /**
     * @var array<string, InMemoryOtpUser>
     */
    private array $users;

    public function __construct()
    {
        $this->users = [
            self::USER_IDENTIFIER => new InMemoryOtpUser(self::USER_IDENTIFIER,  (new Interviewer())
                ->setName('Direct onboarding training user')
                ->setSerialId('9999')
                ->addTrainingAreaPeriod((new AreaPeriod())
                    ->setArea(AreaPeriod::TRAINING_ONBOARDING_AREA_SERIAL)
                    ->setYear((new \DateTime())->format('Y'))
                    ->setMonth((new \DateTime())->format('m'))
            )),
        ];
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof InMemoryOtpUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_debug_type($user)));
        }

        return $this->getUser($user->getUserIdentifier())
            ->setHousehold($user->getHousehold());
    }

    public function supportsClass(string $class): bool
    {
        return $class === InMemoryOtpUser::class;
    }

    /**
     * @deprecated use loadUserByIdentifier() instead
     */
    public function loadUserByUsername(string $username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        return $this->getUser($identifier);
    }

    private function getUser(string $username): InMemoryOtpUser
    {
        if (!isset($this->users[strtolower($username)])) {
            $ex = new UserNotFoundException(sprintf('Username "%s" does not exist.', $username));
            $ex->setUserIdentifier($username);

            throw $ex;
        }

        return $this->users[strtolower($username)];
    }

}