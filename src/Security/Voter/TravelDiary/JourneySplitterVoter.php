<?php

namespace App\Security\Voter\TravelDiary;

use App\Entity\Journey\Journey;
use App\Entity\Journey\Method;
use App\Entity\Journey\Stage;
use App\Entity\User;
use App\Security\ImpersonatorAuthorizationChecker;
use App\Utility\TravelDiary\SplitJourneyHelper;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class JourneySplitterVoter extends Voter
{
    public const CAN_SPLIT_JOURNEY = 'CAN_SPLIT_JOURNEY';

    public function __construct(
        protected ImpersonatorAuthorizationChecker $impersonatorAuthorizationChecker,
        protected SplitJourneyHelper $splitJourneyHelper,
    ) {}

    protected function supports(string $attribute, $subject): bool
    {
        return $attribute === self::CAN_SPLIT_JOURNEY
            && $subject instanceof Journey;
    }

    /**
     * @param Journey $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        if (
            // The user returned by $token->getUser() isn't initialised fully, and has a null
            // $user->interviewer, even if an interviewer. We instead check the user's roles using
            // the impersonatorAuthChecker
            !$this->impersonatorAuthorizationChecker->isGranted(User::ROLE_INTERVIEWER) ||
            $subject->getStages()->count() !== 1
        ) {
            return false;
        }

        $stage = $subject->getStages()->first();
        $midTime = $this->splitJourneyHelper->getMidTime($subject);

        $identicalStartAndEnd =
            $subject->getStartLocation() === $subject->getEndLocation() &&
            $subject->getIsStartHome() === $subject->getIsEndHome();

        return
            $stage instanceof Stage &&
            $stage->getMethod()->getType() === Method::TYPE_OTHER &&
            $identicalStartAndEnd &&
            !$this->splitJourneyHelper->whenSplitWillCrossDayBoundaryIntoDayEight($subject, $midTime) &&
            !$this->splitJourneyHelper->isJourneyTooQuickToSplit($subject);
    }
}