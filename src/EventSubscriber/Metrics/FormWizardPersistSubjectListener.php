<?php

namespace App\EventSubscriber\Metrics;

use App\Controller\TravelDiary\Wizards\JourneyWizardController;
use App\Controller\TravelDiary\Wizards\RepeatJourneyWizardController;
use App\Controller\TravelDiary\Wizards\ReturnJourneyWizardController;
use App\Controller\TravelDiary\Wizards\ShareJourneyWizardController;
use App\Controller\TravelDiary\Wizards\SplitJourneyWizardController;
use App\Controller\TravelDiary\Wizards\StageWizardController;
use App\Entity\Journey\Journey;
use App\Entity\Journey\Stage;
use App\Utility\Metrics\Events\Entity\JourneyEditEvent;
use App\Utility\Metrics\Events\Entity\JourneyPersistEvent;
use App\Utility\Metrics\Events\Entity\JourneyRepeatEvent;
use App\Utility\Metrics\Events\Entity\JourneyReturnEvent;
use App\Utility\Metrics\Events\Entity\JourneySharedEvent;
use App\Utility\Metrics\Events\Entity\JourneySplitEvent;
use App\Utility\Metrics\Events\Entity\StageEditEvent;
use App\Utility\Metrics\Events\Entity\StagePersistEvent;
use App\Utility\Metrics\MetricsHelper;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Ghost\GovUkCoreBundle\Event\FormWizard\PostPersistEvent;
use Ghost\GovUkCoreBundle\Event\FormWizard\PrePersistEvent;
use Ghost\GovUkCoreBundle\FormWizard\MultipleEntityInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

// N.B. The PostPersistEvent handled by this class is *not* a Doctrine lifecycle event
//      (But the preUpdate *is*)
#[AsDoctrineListener(event: Events::preUpdate)]
#[AsEventListener(event: PostPersistEvent::class, method: 'formWizardPostPersist')]
#[AsEventListener(event: PrePersistEvent::class, method: 'formWizardPrePersist')]
class FormWizardPersistSubjectListener
{
    protected array $changeSets = [];
    protected array $prePersistIds = [];

    public function __construct(protected MetricsHelper $metrics) {}

    public function preUpdate(PreUpdateEventArgs $eventArgs): void
    {
        $object = $eventArgs->getObject();
        if (!$object instanceof Stage && !$object instanceof Journey) {
            return;
        }
        $this->changeSets[$object->getId()] = $eventArgs->getEntityChangeSet();
    }

    public function formWizardPrePersist(PrePersistEvent $event): void
    {
        $subject = $event->getSubject();
        if (!$subject instanceof Stage && !$subject instanceof Journey) {
            return;
        }
        $this->prePersistIds[spl_object_id($subject)] = $subject->getId();
    }

    public function formWizardPostPersist(PostPersistEvent $event): void
    {
        $subject = $event->getSubject();
        $subjectAlreadyExists = $this->prePersistIds[spl_object_id($subject)] ?? false;

        $wizardControllerClass = $event->getSourceWizardController();
        if ($subject instanceof MultipleEntityInterface) {
            $subject = $subject->getEntitiesToPersist()[0];
        }
        $changeSet = $this->changeSets[$subject->getId()] ?? [];

        switch ($wizardControllerClass) {
            case ShareJourneyWizardController::class :
                /** @var Journey $subject */
                $this->metrics->log(new JourneySharedEvent($subject));
                return;

            case ReturnJourneyWizardController::class :
                $this->metrics->log(new JourneyReturnEvent($subject));
                return;

            case RepeatJourneyWizardController::class :
                $this->metrics->log(new JourneyRepeatEvent($subject));
                return;

            case SplitJourneyWizardController::class :
                $this->metrics->log(new JourneySplitEvent($subject));
                return;

            case JourneyWizardController::class :
                $subjectAlreadyExists
                    ? $this->metrics->log(new JourneyEditEvent($subject, $changeSet))
                    : $this->metrics->log(new JourneyPersistEvent($subject));
                return;

            case StageWizardController::class :
                $subjectAlreadyExists
                    ? $this->metrics->log(new StageEditEvent($subject, $changeSet))
                    : $this->metrics->log(new StagePersistEvent($subject));
                return;

            // It's some other wizard that doesn't require metrics logging
        }
    }
}
