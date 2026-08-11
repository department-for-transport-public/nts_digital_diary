<?php

namespace App\EventSubscriber\Metrics;

use App\Entity\Journey\Journey;
use App\Entity\Journey\Stage;
use App\FormWizard\TravelDiary\CombinedReflowState;
use App\Utility\Metrics\Events\Entity\JourneyEditEvent;
use App\Utility\Metrics\Events\Entity\JourneyPersistEvent;
use App\Utility\Metrics\Events\Entity\JourneyRepeatEvent;
use App\Utility\Metrics\Events\Entity\JourneyReturnEvent;
use App\Utility\Metrics\Events\Entity\StageEditEvent;
use App\Utility\Metrics\Events\Entity\StagePersistEvent;
use App\Utility\Metrics\MetricsHelper;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\TransitionEvent;

#[AsDoctrineListener(event: Events::preUpdate)]
#[AsEventListener(event: 'workflow.form_wizard.travel_diary.combined_reflow.transition', method: 'onCombinedReflowTransition', priority: -128)]
class ReflowFormWizardPersistSubjectListener
{
    protected array $changeSets = [];

    public function __construct(protected MetricsHelper $metrics) {}

    public function preUpdate(PreUpdateEventArgs $eventArgs): void
    {
        $object = $eventArgs->getObject();
        if (!$object instanceof Stage && !$object instanceof Journey) {
            return;
        }
        $this->changeSets[$object->getId()] = $eventArgs->getEntityChangeSet();
    }

    public function getChangeSet($object): array
    {
        return $this->changeSets[$object->getId()] ?? [];
    }

    public function onCombinedReflowTransition(TransitionEvent $event): void
    {
        if ($event->getMetadata('persist', $event->getTransition()) !== true) {
            return;
        };

        /** @var CombinedReflowState $state */
        $state = $event->getSubject();
        $transitionName = $event->getTransition()->getName();
        switch(true) {
            case $transitionName === 'standard__details__stage_method' :
                $this->metrics->log(new JourneyPersistEvent($state->getSubject()));
                return;

            case $transitionName === 'standard__details__finish' :
                $this->metrics->log(new JourneyEditEvent($state->getSubject(), $this->getChangeSet($state->getSubject())));
                return;

            case preg_match('/^standard__stage_[a-z_]*__stage_add_another$/', $transitionName)
                    && in_array($state->getWizardType(), [CombinedReflowState::WIZARD_TYPE__ADD, null]) :
                $this->metrics->log(new StagePersistEvent($state->getStage()));
                return;

            case $transitionName === 'standard__stage_finish' :
                $this->metrics->log(new StageEditEvent($state->getStage(), $this->getChangeSet($state->getStage())));
                return;

            case preg_match('/^repeat__[a-z_]*__finish$/', $transitionName) :
            case preg_match('/^standard__stage_[a-z_]*__stage_add_another$/', $transitionName)
                && $state->getWizardType() === CombinedReflowState::WIZARD_TYPE__REPEAT :
                $this->metrics->log(new JourneyRepeatEvent($state->getSubject()));
                return;

            case preg_match('/^return__[a-z_]*__finish$/', $transitionName) :
            case preg_match('/^standard__stage_[a-z_]*__stage_add_another$/', $transitionName)
                && $state->getWizardType() === CombinedReflowState::WIZARD_TYPE__RETURN :
                $this->metrics->log(new JourneyReturnEvent($state->getSubject()));
                return;

            default :
                return;
        }
    }
}