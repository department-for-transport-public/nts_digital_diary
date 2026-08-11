<?php

namespace App\Security\Voter\Interviewer;

use App\Entity\DiaryKeeper;
use App\Entity\Household;
use App\Entity\User;
use App\Security\OneTimePassword\InMemoryOtpUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class InterviewerTrainingVoter extends Voter
{
    public const IS_INTERVIEWER_TRAINING = 'IS_INTERVIEWER_TRAINING';

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected RequestStack           $requestStack,
        protected Security               $security,
    ) {}

    protected function supports(string $attribute, $subject): bool
    {
        return $attribute === self::IS_INTERVIEWER_TRAINING;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        return
            $this->isInterviewerTrainingRoute()
            || $this->isUsingPersonalTravelDiary()
            || $this->isUsingOnboardingLoginInTrainingMode()
            || $this->isUsingOnboardingInTrainingMode()
            || $this->isStartingImpersonationForTraining()
            || $this->isUsingDiaryCorrectionTraining();
    }

    protected function isStartingImpersonationForTraining(): bool
    {
        if ($url = $this->requestStack?->getCurrentRequest()?->attributes?->get('switch_user_start')) {
            $url = parse_url((string)$url);
            if (str_starts_with($url['path'] ?? '', '/interviewer/training')) {
                return true;
            }
        }
        return false;
    }

    protected function isInterviewerTrainingRoute(): bool
    {
        return str_starts_with($this->requestStack->getCurrentRequest()?->getRequestUri() ?? '', '/interviewer/training');
    }

    protected function isUsingDiaryCorrectionTraining(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        $diaryKeeperId = $request?->attributes?->get('diaryKeeper');
        $householdId = $request?->attributes?->get('household');

        if ($diaryKeeperId) {
            $diaryKeeper = $this->entityManager
                ->getRepository(DiaryKeeper::class)
                ->createQueryBuilder('diaryKeeper')
                ->select('diaryKeeper, household, areaPeriod, trainingInterviewer')
                ->leftJoin('diaryKeeper.household', 'household')
                ->leftJoin('household.areaPeriod', 'areaPeriod')
                ->leftJoin('areaPeriod.trainingInterviewer', 'trainingInterviewer')
                ->where('diaryKeeper.id = :diaryKeeperId')
                ->setParameter('diaryKeeperId', $diaryKeeperId)
                ->getQuery()
                ->getOneOrNullResult();

            if (!$diaryKeeper instanceof DiaryKeeper) {
                throw new \RuntimeException('No such diaryKeeper');
            }

            $household = $diaryKeeper->getHousehold();
        } else if ($householdId) {
            $household = $this->entityManager
                ->getRepository(Household::class)
                ->createQueryBuilder('household')
                ->select('household, areaPeriod, trainingInterviewer')
                ->leftJoin('household.areaPeriod', 'areaPeriod')
                ->leftJoin('areaPeriod.trainingInterviewer', 'trainingInterviewer')
                ->where('household.id = :householdId')
                ->setParameter('householdId', $householdId)
                ->getQuery()
                ->getOneOrNullResult();

            if (!$household instanceof Household) {
                throw new \RuntimeException('No such household');
            }
        } else {
            $household = null;
        }

        return ($household && $household->isTraining());
    }

    protected function isUsingPersonalTravelDiary(): bool
    {
        $user = $this->security->getUser();
        return $user instanceof User && $user->getTrainingInterviewer();
    }

    protected function isUsingOnboardingLoginInTrainingMode(): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        return $request?->query?->has('_interviewer') && $request?->query?->has('_signature');
    }

    protected function isUsingOnboardingInTrainingMode(): bool
    {
        $user = $this->security->getUser();
        return $user instanceof InMemoryOtpUser;
    }
}
