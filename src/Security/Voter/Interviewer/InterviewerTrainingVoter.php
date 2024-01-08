<?php

namespace App\Security\Voter\Interviewer;

use App\Entity\DiaryKeeper;
use App\Entity\Household;
use App\Entity\User;
use App\Security\OneTimePassword\FormAuthenticator;
use App\Security\OneTimePassword\InMemoryOtpUser;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class InterviewerTrainingVoter extends Voter
{
    public const IS_INTERVIEWER_TRAINING = 'IS_INTERVIEWER_TRAINING';
    public function __construct(
        protected RequestStack $requestStack,
        protected Security $security,
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
            || $this->isUsingDiaryCorrectionTraining()
            ;
    }

    protected function isStartingImpersonationForTraining(): bool
    {
        if ($url = $this->requestStack?->getCurrentRequest()?->attributes?->get('switch_user_start')) {
            $url = parse_url($url);
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

        /** @var ?DiaryKeeper $diaryKeeper */
        $diaryKeeper = $request?->attributes?->get('diaryKeeper');

        if ($diaryKeeper) {
            $household = $diaryKeeper->getHousehold();
        } else {
            /** @var ?Household $household */
            $household = $request?->attributes?->get('household');
        }

        if ($household && $household->getAreaPeriod()->getTrainingInterviewer()) {
            return true;
        }

        return false;
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