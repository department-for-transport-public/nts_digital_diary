<?php

namespace App\Utility\ConfirmAction\Admin;

use App\Entity\AreaPeriod;
use App\Entity\Interviewer;
use App\Utility\ConfirmAction\AbstractConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class InterviewerDeallocateAreaConfirmAction extends AbstractConfirmAction
{
    /** @var Interviewer */
    protected $subject;
    protected EntityManagerInterface $entityManager;

    protected AreaPeriod $areaPeriod;

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, EntityManagerInterface $entityManager)
    {
        parent::__construct($formFactory, $requestStack);
        $this->entityManager = $entityManager;
    }

    public function setAreaPeriod(AreaPeriod $areaPeriod): self
    {
        $this->areaPeriod = $areaPeriod;
        return $this;
    }

    public function getAreaPeriod(): AreaPeriod
    {
        return $this->areaPeriod;
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
            'area' => $this->areaPeriod->getArea(),
        ];
    }

    public function getTranslationDomain(): ?string
    {
        return 'admin';
    }

    public function getTranslationKeyPrefix(): string
    {
        return 'interviewer.deallocate';
    }

    public function doConfirmedAction($formData)
    {
        $this->subject->removeAreaPeriod($this->areaPeriod);
        $this->entityManager->flush();
    }
}