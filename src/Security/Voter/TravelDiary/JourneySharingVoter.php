<?php

namespace App\Security\Voter\TravelDiary;

use App\Entity\DiaryKeeper;
use App\Entity\Journey\Journey;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class JourneySharingVoter extends Voter
{
    const CAN_SHARE_JOURNEY = 'CAN_SHARE_JOURNEY';
    const CAN_SHARE_JOURNEYS = 'CAN_SHARE_JOURNEYS';
    const CAN_SHARE_WITH_DIARY_KEEPER = 'CAN_SHARE_WITH_DIARY_KEEPER';

    protected function supports(string $attribute, $subject): bool
    {
        return
            ($attribute === self::CAN_SHARE_JOURNEY && $subject instanceof Journey) ||
            ($attribute === self::CAN_SHARE_JOURNEYS && is_null($subject)) ||
            ($attribute === self::CAN_SHARE_WITH_DIARY_KEEPER && $subject instanceof DiaryKeeper);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        switch($attribute) {
            case self::CAN_SHARE_JOURNEYS:
                $diaryKeeper = $this->getDiaryKeeperForToken($token);

                if (!$diaryKeeper) {
                    return false;
                }

                $household = $diaryKeeper->getHousehold();
                return
                    $household->getDiaryKeepers()->count() > 1 &&
                    (
                        $diaryKeeper->isActingAsAProxyForOthers() ||
                        $household->isJourneySharingEnabled()
                    );

            case self::CAN_SHARE_JOURNEY:
                /** @var $subject Journey */

                if (
                    $subject->isShared() || // For the moment, we are not allowing DKs to share a journey they have already shared
                    $subject->wasCreatedBySharing() ||
                    $subject->getStages()->isEmpty() ||
                    $subject->getMinimumStageTravellerCount() < 2
                ) {
                    return false;
                }

                $diaryKeeper = $this->getDiaryKeeperForToken($token);
                $household = $diaryKeeper->getHousehold();

                foreach($household->getDiaryKeepers() as $householdDiaryKeeper) {
                    if ($diaryKeeper !== $householdDiaryKeeper &&
                        $this->voteOnAttribute(self::CAN_SHARE_WITH_DIARY_KEEPER, $householdDiaryKeeper, $token) &&
                        !$subject->isSharedWithDiaryKeeper($householdDiaryKeeper)
                    ) {
                        // At least one diary-keeper exists with whom we are allowed to share this journey, and we
                        // haven't done so before...
                        return true;
                    }
                }

                return false;

            case self::CAN_SHARE_WITH_DIARY_KEEPER: // (in principle)
                // e.g. we might be able to share in principle with this diary keeper, but might not in actuality
                //      due to having already shared it with them.
                /** @var $subject DiaryKeeper */

                if (!in_array($subject->getDiaryState(), [DiaryKeeper::STATE_NEW, DiaryKeeper::STATE_IN_PROGRESS])) {
                    return false;
                }

                if ($subject->getHousehold()->isJourneySharingEnabled()) {
                    return true;
                }

                $userDiaryKeeper = $this->getDiaryKeeperForToken($token);
                return $userDiaryKeeper && $userDiaryKeeper->isActingAsProxyFor($subject);
        }

        return false;
    }

    protected function getDiaryKeeperForToken(TokenInterface $token): ?DiaryKeeper
    {
        $user = $token->getUser();
        return ($user instanceof User) ?
            $user->getDiaryKeeper() :
            null;
    }
}