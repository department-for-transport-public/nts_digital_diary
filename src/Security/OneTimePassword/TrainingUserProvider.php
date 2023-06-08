<?php

namespace App\Security\OneTimePassword;

use App\Entity\Household;
use App\Entity\Interviewer;
use App\Entity\InterviewerTrainingRecord;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class TrainingUserProvider implements UserProviderInterface
{
    public const USER_IDENTIFIER = "12345678";
    /**
     * @var array<string, UserInterface>
     */
    private array $users;
    private EntityManagerInterface $entityManager;

    public function __construct(RequestStack $requestStack, EntityManagerInterface $entityManager)
    {
        if ($requestStack->getCurrentRequest()->query->has('_interviewer')) {
            $interviewer = $entityManager->find(Interviewer::class, $requestStack->getCurrentRequest()->query->get('_interviewer'));
        }

        $this->users = [
            self::USER_IDENTIFIER => new InMemoryOtpUser(self::USER_IDENTIFIER,  $interviewer ?? null),
        ];

        $this->entityManager = $entityManager;
    }

    /**
     * this is called for each request. Because the InMemoryOtpUser isn't persisted, we need to reload the related
     * entities (interviewer/household) from the database
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof InMemoryOtpUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_debug_type($user)));
        }

        $storedUser = $this->getUser($user->getUserIdentifier());

        $interviewer = $this->entityManager->find(Interviewer::class, $user->getInterviewer()->getId());

        /**
         * During the onboarding/household wizard, the household hasn't yet been persisted.
         * We need to cope with either a persisted household, or an as yet un-persisted one.
         */
        $household = ($user->getHousehold() && $user->getHousehold()->getId())
            ? $this->entityManager->find(Household::class, $user->getHousehold()->getId())
            : $user->getHousehold();

        if ($household) {
            // save reference to household
            $interviewer
                ->getLatestTrainingRecordForModule(InterviewerTrainingRecord::MODULE_ONBOARDING)
                ->setHousehold($household);
        } else {
            // retrieve household from most recent onboarding module record
            $household = $interviewer
                ->getLatestTrainingRecordForModule(InterviewerTrainingRecord::MODULE_ONBOARDING)
                ->getHousehold();
        }

        return new InMemoryOtpUser($storedUser->getUserIdentifier(), $interviewer, $household ?? null);
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

    private function getUser(string $username)/*: InMemoryUser */
    {
        if (!isset($this->users[strtolower($username)])) {
            $ex = new UserNotFoundException(sprintf('Username "%s" does not exist.', $username));
            $ex->setUserIdentifier($username);

            throw $ex;
        }

        return $this->users[strtolower($username)];
    }

}