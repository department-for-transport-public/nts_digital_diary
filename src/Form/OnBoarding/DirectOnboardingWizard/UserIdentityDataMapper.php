<?php

namespace App\Form\OnBoarding\DirectOnboardingWizard;

use App\Entity\DiaryKeeper;
use App\Entity\User;
use Symfony\Component\Form\Extension\Core\DataMapper\DataMapper;
use Symfony\Component\Form\FormInterface;

class UserIdentityDataMapper extends DataMapper
{
    /**
     * @param DiaryKeeper $data
     */
    public function mapDataToForms(mixed $data, \Traversable $forms): void
    {
        $traversableForms = $forms;

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        if (!isset($forms['user'])) {
            return;
        }

        if (!$data->getUser()) {
            $data->setUser(new User());
        }

        parent::mapDataToForms($data, $traversableForms);

        $user = $data->getUser();
        $forms['user']->get('username')->setData($user->hasIdentifierForLogin() ? $user->getUserIdentifier() : '');
    }

    /**
     * @param DiaryKeeper $data
     */
    public function mapFormsToData(\Traversable $forms, mixed &$data): void
    {
        $traversableForms = $forms;

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        if (!isset($forms['user'])) {
            return;
        }

        $user = $data->getUser();
        $currentUserIdentifier = $user->getUserIdentifier();

        parent::mapFormsToData($traversableForms, $data);

        $user->getDiaryKeeper()->setMediaType(DiaryKeeper::MEDIA_TYPE_DIGITAL);

        if (!$user->getUserIdentifier()) {
            $newUserIdentifier = User::isNoLoginPlaceholder($currentUserIdentifier)
                ? $currentUserIdentifier
                : User::generateNoLoginPlaceholder();

            $user
                ->setUserIdentifier($newUserIdentifier);
        }

        if (!$user->hasIdentifierForLogin()) {
            $proxyingFor = $user->getDiaryKeeper()->getActingAsProxyFor()->toArray();
            array_walk($proxyingFor, fn(DiaryKeeper $p) => $user->getDiaryKeeper()->removeActingAsProxyFor($p));
        }
    }
}