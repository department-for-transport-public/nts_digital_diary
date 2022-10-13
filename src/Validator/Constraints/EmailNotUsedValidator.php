<?php

namespace App\Validator\Constraints;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class EmailNotUsedValidator extends ConstraintValidator
{
    protected UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof EmailNotUsed) {
            throw new UnexpectedTypeException($constraint, EmailNotUsed::class);
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

        if ($this->userRepository->isExistingUserWithEmailAddress($value, $userId)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
