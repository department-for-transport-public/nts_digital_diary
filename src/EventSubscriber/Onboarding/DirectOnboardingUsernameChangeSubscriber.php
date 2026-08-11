<?php

namespace App\EventSubscriber\Onboarding;

use App\Entity\User;
use App\FormWizard\OnBoarding\DirectOnboardingState;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\LeaveEvent;

/**
 * When a username is moved up the list of diary keepers
 * (e.g. from the 2nd to the 1st DK) the database tries to update the earlier one before emptying the later one,
 * causing a UniqueConstraintViolation.
 * (The older similar bug was caused by Symfony's form validation, which queried the database to check for
 * duplicates and obviously found one)
 */
class DirectOnboardingUsernameChangeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.form_wizard.on_boarding.direct_onboarding.leave.' . DirectOnboardingState::STATE_DIARY_KEEPER_LOGINS => 'preventDuplicateDiaryKeeperProblem',
        ];
    }

    public function preventDuplicateDiaryKeeperProblem(LeaveEvent $event): void
    {
        /** @var DirectOnboardingState $state */
        $state = $event->getSubject();

        foreach ($state->getSubject()->getDiaryKeepers() as $diaryKeeper) {
            $this->entityManager->createQueryBuilder()
                ->update(User::class, 'u')
                ->set('u.username', ':tempUsername')
                ->where('u.id = :user')
                ->andWhere('u.username != :newUsername')
                ->setParameter('tempUsername', User::generateNoLoginPlaceholder())
                ->setParameter('newUsername', $diaryKeeper->getUser()->getUsername())
                ->setParameter('user', $diaryKeeper->getUser()->getId())
                ->getQuery()
                ->execute();
        }
    }
}