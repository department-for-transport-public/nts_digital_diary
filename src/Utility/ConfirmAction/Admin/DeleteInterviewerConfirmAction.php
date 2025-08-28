<?php

namespace App\Utility\ConfirmAction\Admin;

use App\Entity\Interviewer;
use App\Utility\ConfirmAction\AbstractConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class DeleteInterviewerConfirmAction extends AbstractConfirmAction
{
    /** @var Interviewer */
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
            ],
        ]);
    }

    public function getTranslationParameters(): array
    {
        return [
            'email' => $this->subject->getUser()->getUserIdentifier(),
            'name' => $this->subject->getName(),
        ];
    }

    public function getTranslationDomain(): ?string
    {
        return 'admin';
    }

    public function getTranslationKeyPrefix(): string
    {
        return 'interviewer.delete';
    }

    public function doConfirmedAction($formData): void
    {
        foreach ($this->subject->getAreaPeriods() as $areaPeriod) {
            $this->subject->removeAreaPeriod($areaPeriod);
        }

        $this->entityManager->flush();
        $this->entityManager->remove($this->subject->getUser());
        $this->entityManager->remove($this->subject);
        $this->entityManager->flush();
    }
}