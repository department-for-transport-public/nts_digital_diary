<?php

namespace App\Security\Voter\TravelDiary;

use App\Entity\Journey\Journey;
use App\Entity\Journey\Stage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DiaryAccessVoter extends Voter
{
    public const ACCESS = 'ACCESS';

    protected function supports(string $attribute, $subject): bool
    {
        return $attribute === self::ACCESS
            && in_array(
                get_class($subject),
                [Journey::class, Stage::class]
            );
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $diaryKeeper = match(get_class($subject)) {
            Journey::class => $subject->getDiaryDay()->getDiaryKeeper(),
            Stage::class => $subject->getJourney()->getDiaryDay()->getDiaryKeeper(),
        };

        return ($token->getUser() === $diaryKeeper->getUser());
    }
}