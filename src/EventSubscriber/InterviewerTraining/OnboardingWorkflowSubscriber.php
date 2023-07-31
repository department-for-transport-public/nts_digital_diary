<?php

namespace App\EventSubscriber\InterviewerTraining;

use App\Entity\AreaPeriod;
use App\Entity\InterviewerTrainingRecord;
use App\Event\CompleteOnboardingEvent;
use App\Security\OneTimePassword\InMemoryOtpUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Workflow\Registry;

class OnboardingWorkflowSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly Registry $workflowRegistry, private readonly EntityManagerInterface $entityManager) {}

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLogin',
            CompleteOnboardingEvent::class => 'onboardingComplete'
        ];
    }

    public function onLogin(LoginSuccessEvent $event)
    {
        $user = $event->getUser();
        if ($user instanceof InMemoryOtpUser) {
            // get the training record
            $trainingRecord = $user
                ->getInterviewer()
                ->getLatestTrainingRecordForModule(InterviewerTrainingRecord::MODULE_ONBOARDING_PRACTICE);
            $workflow = $this->workflowRegistry->get($trainingRecord);
            if ($workflow->can($trainingRecord, InterviewerTrainingRecord::TRANSITION_START)) {
                $workflow->apply($trainingRecord, InterviewerTrainingRecord::TRANSITION_START);
            }
            $this->entityManager->flush();
        }
    }

    public function onboardingComplete(CompleteOnboardingEvent $event)
    {
        $household = $event->getHousehold();
        if ($household->getAreaPeriod()->getArea() !== AreaPeriod::TRAINING_ONBOARDING_AREA_SERIAL) {
            return;
        }
        $trainingRecord = $household->getAreaPeriod()->getLatestTrainingRecord();
        $workflow = $this->workflowRegistry->get($trainingRecord);
        if ($workflow->can($trainingRecord, InterviewerTrainingRecord::TRANSITION_COMPLETE)) {
            $workflow->apply($trainingRecord, InterviewerTrainingRecord::TRANSITION_COMPLETE);
        }
        $this->entityManager->flush();
    }

}
