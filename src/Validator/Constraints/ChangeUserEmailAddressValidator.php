<?php

namespace App\Validator\Constraints;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ChangeUserEmailAddressValidator extends ConstraintValidator
{
    protected UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ChangeUserEmailAddress) {
            throw new UnexpectedTypeException($constraint, ChangeUserEmailAddress::class);
        }


        if (null === $value) {
            return;
        }

        if (!($userId = $constraint->userId)) {
            $user = $this->context->getObject();

            if (!$user instanceof User) {
                return;
            }

            $userId = $user->getId();
        }

        if ($this->userRepository->canChangeEmailTo($value, $userId)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
