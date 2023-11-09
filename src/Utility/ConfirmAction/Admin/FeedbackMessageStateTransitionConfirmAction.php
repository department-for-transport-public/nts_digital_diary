<?php

namespace App\Utility\ConfirmAction\Admin;

use App\Entity\Feedback\Message;
use App\Utility\ConfirmAction\AbstractConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Workflow\WorkflowInterface;

class FeedbackMessageStateTransitionConfirmAction extends AbstractConfirmAction
{
    /** @var Message */
    protected $subject;
    protected string $transition;

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, protected WorkflowInterface $feedbackMessageStateMachine, protected EntityManagerInterface $entityManager)
    {
        parent::__construct($formFactory, $requestStack);
    }

    public function getExtraViewData(): array
    {
        return [
            'transition' => $this->getTransition(),
        ];
    }

    public function getTranslationKeyPrefix(): string
    {
        return "feedback.state-transition.{$this->getTransition()}";
    }

    public function doConfirmedAction($formData)
    {
        $this->feedbackMessageStateMachine->apply($this->getMessage(), $this->getTransition());
        $this->entityManager->flush();
    }

    public function getTransition(): string
    {
        return $this->transition;
    }

    public function getTranslationDomain(): ?string
    {
        return 'admin';
    }

    public function setTransition(string $transition): self
    {
        $this->transition = $transition;
        return $this;
    }

    public function getMessage(): Message
    {
        return $this->subject;
    }
}