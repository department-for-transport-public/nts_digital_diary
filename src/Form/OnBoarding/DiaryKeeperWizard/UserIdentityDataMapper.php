<?php

namespace App\Form\OnBoarding\DiaryKeeperWizard;

use App\Entity\DiaryKeeper;
use App\Entity\User;
use Symfony\Component\Form\Extension\Core\DataMapper\DataMapper;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Uid\Ulid;
use Traversable;

class UserIdentityDataMapper extends DataMapper
{
    /**
     * @param Traversable|iterable $forms
     * @param DiaryKeeper $data
     */
    public function mapDataToForms($data, iterable $forms): void
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        if (!isset($forms['user'])) {
            return;
        }

        if (!$data->getUser()) {
            $data->setUser(new User());
        }

        parent::mapDataToForms($data, $forms);

        $user = $data->getUser();

        if ($user->hasIdentifierForLogin()) {
            $userIdentifier = $user->getUserIdentifier();
//            $consent = $user->getHasConsented();
        } else {
            $userIdentifier = '';
//            $consent = null;
        }

        $forms['user']->get('username')->setData($userIdentifier);
//        $forms['user']->get('consent')->setData($consent);
    }

    /**
     * @param Traversable|iterable $forms
     * @param DiaryKeeper $data
     */
    public function mapFormsToData(iterable $forms, &$data): void
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        if (!isset($forms['user'])) {
            return;
        }

        $user = $data->getUser();
        $currentUserIdentifier = $user->getUserIdentifier();

        parent::mapFormsToData($forms, $data);

        if (!$user->getUserIdentifier()) {
            $newUserIdentifier = User::isNoLoginPlaceholder($currentUserIdentifier) ?
                $currentUserIdentifier :
                (User::NO_LOGIN_PLACEHOLDER.':'.(new Ulid()));

            $user
                ->setUserIdentifier($newUserIdentifier);
//                ->setHasConsented(null);
        }
    }
}