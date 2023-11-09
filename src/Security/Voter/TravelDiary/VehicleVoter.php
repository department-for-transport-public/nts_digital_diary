<?php

namespace App\Security\Voter\TravelDiary;

use App\Entity\User;
use App\Entity\Vehicle;
use App\Features;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class VehicleVoter extends Voter
{
    public const EDIT_VEHICLE = 'EDIT_VEHICLE';

    public function __construct(
        protected Features $features
    ) {}

    protected function supports(string $attribute, $subject): bool
    {
        return $attribute === self::EDIT_VEHICLE
            && $subject instanceof Vehicle
            && $this->features->isEnabled(Features::MILOMETER);
    }

    /**
     * @param Vehicle $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        return $user->getDiaryKeeper()->getPrimaryDriverVehicles()->exists(
            fn($k, Vehicle $v) => $v->getId() === $subject->getId()
        );
    }
}