<?php

namespace App\Form\OnBoarding\DiaryKeeperWizard;

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

        if (in_array($data->getMediaType(), [DiaryKeeper::MEDIA_TYPE_INTERVIEWER_TRANSCRIBED, DiaryKeeper::MEDIA_TYPE_PAPER])) {
            $forms['mediaType']->setData('other');
            $forms['interviewerMediaType']->setData($data->getMediaType());
        }

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
        if ($data->getMediaType() === 'other' && $forms['interviewerMediaType']->getData()) {
            $data->setMediaType($forms['interviewerMediaType']->getData());
        }

        if (in_array($data->getMediaType(), [DiaryKeeper::MEDIA_TYPE_INTERVIEWER_TRANSCRIBED, DiaryKeeper::MEDIA_TYPE_PAPER])) {
            $user->setUserIdentifier(null);
            $proxies = $data->getProxies()->toArray();
            array_walk($proxies, fn($p) => $data->removeProxy($p));
        }

        if (!$user->getUserIdentifier()) {
            $newUserIdentifier = User::isNoLoginPlaceholder($currentUserIdentifier)
                ? $currentUserIdentifier
                : User::generateNoLoginPlaceholder();

            $user
                ->setUserIdentifier($newUserIdentifier);
//                ->setHasConsented(null);
        }
    }
}