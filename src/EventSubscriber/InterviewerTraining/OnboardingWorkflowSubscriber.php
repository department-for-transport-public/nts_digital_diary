<?php

namespace App\EventSubscriber\InterviewerTraining;

use App\Entity\AreaPeriod;
use App\Entity\InterviewerTrainingRecord;
use App\Event\CompleteOnboardingEvent;
use App\Security\OneTimePassword\InMemoryOtpUser;
use App\Security\Voter\Onboarding\DirectOnboardingTrainingVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Workflow\Registry;

class OnboardingWorkflowSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected Registry               $workflowRegistry,
        protected EntityManagerInterface $entityManager,
        protected Security               $security,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLogin',
            CompleteOnboardingEvent::class => 'onboardingComplete'
        ];
    }

    public function onLogin(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if ($user instanceof InMemoryOtpUser) {
            // get the training record
            $trainingRecord = $user
                ->getInterviewer()
                ->getLatestTrainingRecordForModule(InterviewerTrainingRecord::MODULE_MANUAL_ONBOARDING_PRACTICE);
            if ($trainingRecord) {
                $workflow = $this->workflowRegistry->get($trainingRecord);
                if ($workflow->can($trainingRecord, InterviewerTrainingRecord::TRANSITION_START)) {
                    $workflow->apply($trainingRecord, InterviewerTrainingRecord::TRANSITION_START);
                }
                $this->entityManager->flush();
            }
        }
    }

    public function onboardingComplete(CompleteOnboardingEvent $event): void
    {
        $household = $event->getHousehold();
        if ($household->getAreaPeriod()->getArea() !== AreaPeriod::TRAINING_ONBOARDING_AREA_SERIAL) {
            return;
        }

        if ($this->security->isGranted(DirectOnboardingTrainingVoter::DIRECT_ONBOARDING_TRAINING)) {
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
