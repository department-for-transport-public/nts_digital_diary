<?php

namespace App\Utility\ConfirmAction\OnBoarding;

use App\Entity\DiaryKeeper;
use App\Utility\ConfirmAction\AbstractConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class DeleteVehicleConfirmAction extends AbstractConfirmAction
{
    /** @var DiaryKeeper | object */
    protected $subject;

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, protected EntityManagerInterface $entityManager)
    {
        parent::__construct($formFactory, $requestStack);
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
        return 'vehicle.delete';
    }

    public function getTranslationParameters(): array
    {
        return [
            'name' => $this->getSubject()->getFriendlyName(),
        ];
    }

    public function doConfirmedAction($formData): void
    {
        $this->entityManager->remove($this->subject);
        $this->entityManager->flush();
    }
}