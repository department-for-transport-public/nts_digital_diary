<?php

namespace App\Utility\ConfirmAction\OnBoarding;

use App\Entity\DiaryKeeper;
use App\Utility\ConfirmAction\AbstractConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class DeleteDiaryKeeperConfirmAction extends AbstractConfirmAction
{
    /** @var DiaryKeeper | object */
    protected $subject;
    protected EntityManagerInterface $entityManager;

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, EntityManagerInterface $entityManager)
    {
        parent::__construct($formFactory, $requestStack);
        $this->entityManager = $entityManager;
    }

    public function getFormOptions(): array
    {
        return array_merge(parent::getFormOptions(), [
            'confirm_button_options' => [
                'attr' => ['class' => 'govuk-button--warning'],
                'label' => 'diary-keeper.delete.confirm-label',
                'label_translation_parameters' => $this->getTranslationParameters(),
            ],
        ]);
    }

    public function getTranslationDomain(): ?string
    {
        return 'on-boarding';
    }

    public function getTranslationKeyPrefix(): string
    {
        return 'diary-keeper.delete';
    }

    public function getTranslationParameters(): array
    {
        return [
            'name' => $this->getSubject()->getName(),
        ];
    }

    public function doConfirmedAction($formData)
    {
        $this->entityManager->remove($this->subject);
        $this->entityManager->flush();
    }
}