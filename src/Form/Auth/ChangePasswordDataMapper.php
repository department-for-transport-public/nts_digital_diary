<?php

namespace App\Form\Auth;

use App\Entity\User;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormInterface;
use Traversable;

class ChangePasswordDataMapper implements DataMapperInterface
{
    public function mapDataToForms($viewData, iterable $forms): void
    {
    }

    /**
     * @param User | null $viewData
     * @param Traversable|iterable $forms
     */
    public function mapFormsToData(iterable $forms, &$viewData): void
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        $password1 = $forms['password1']->getData();
        $password2 = $forms['password2']->getData();

        if ($password1 !== null && $password1 === $password2) {
            $viewData->setPlainPassword($password1);
        }
    }
}