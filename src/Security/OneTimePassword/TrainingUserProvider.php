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
    public const INTERVIEWER_ID_QUERY_PARAM = "_interviewer";

    /**
     * @var array<string, UserInterface>
     */
    private array $users;
    private EntityManagerInterface $entityManager;

    public function __construct(RequestStack $requestStack, EntityManagerInterface $entityManager)
    {
        $interviewerId = $requestStack->getCurrentRequest()->query
            ->get(TrainingUserProvider::INTERVIEWER_ID_QUERY_PARAM);

        if ($interviewerId) {
            $interviewer = $entityManager->find(Interviewer::class, $interviewerId);
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

        $interviewerId = $user->getInterviewer()?->getId();
        $interviewer = $this->entityManager->find(Interviewer::class, $interviewerId);

        if (null === $interviewer) {
            $e = new UserNotFoundException('User with id '.json_encode($interviewerId).' not found.');
            $e->setUserIdentifier(json_encode($interviewerId));

            throw $e;
        }

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
                ->getLatestTrainingRecordForModule(InterviewerTrainingRecord::MODULE_ONBOARDING_PRACTICE)
                ->setHousehold($household);
        } else {
            // retrieve household from most recent onboarding module record
            $household = $interviewer
                ->getLatestTrainingRecordForModule(InterviewerTrainingRecord::MODULE_ONBOARDING_PRACTICE)
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