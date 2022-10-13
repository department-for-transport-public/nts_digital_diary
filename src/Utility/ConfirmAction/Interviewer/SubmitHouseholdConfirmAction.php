<?php

namespace App\Utility\ConfirmAction\Interviewer;

use App\Entity\Household;
use App\Utility\ConfirmAction\AbstractConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class SubmitHouseholdConfirmAction extends AbstractConfirmAction
{
    /** @var Household | object */
    protected $subject;
    protected EntityManagerInterface $entityManager;
    protected TokenStorageInterface $tokenStorage;

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage)
    {
        parent::__construct($formFactory, $requestStack);
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
    }

    public function getFormOptions(): array
    {
        return array_merge(parent::getFormOptions(), [
            'confirm_button_options' => [
                'attr' => ['class' => 'govuk-button'],
                'label' => 'household.submit.confirm-label',
                'label_translation_parameters' => $this->getTranslationParameters(),
            ],
        ]);
    }

    public function getTranslationDomain(): ?string
    {
        return 'interviewer';
    }

    public function getTranslationKeyPrefix(): string
    {
        return 'household.submit';
    }

    public function getTranslationParameters(): array
    {
        return [
            'serial' => $this->subject->getSerialNumber(),
        ];
    }

    public function doConfirmedAction($formData)
    {
        $this->subject->setSubmittedAt(new \DateTime());
        $this->subject->setSubmittedBy($this->tokenStorage->getToken()->getUser()->getUserIdentifier());
        $this->entityManager->flush();
    }
}
