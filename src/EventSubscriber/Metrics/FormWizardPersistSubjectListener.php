<?php

namespace App\EventSubscriber\Metrics;

use App\Controller\TravelDiary\JourneyWizardController;
use App\Controller\TravelDiary\RepeatJourneyWizardController;
use App\Controller\TravelDiary\ReturnJourneyWizardController;
use App\Controller\TravelDiary\ShareJourneyWizardController;
use App\Controller\TravelDiary\SplitJourneyWizardController;
use App\Controller\TravelDiary\StageWizardController;
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
use Ghost\GovUkCoreBundle\FormWizard\MultipleEntityInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

// N.B. The PostPersistEvent handled by this class is *not* a Doctrine lifecycle event
//      (But the preUpdate *is*)
#[AsDoctrineListener(event: Events::preUpdate)]
#[AsEventListener(event: PostPersistEvent::class, method: 'postPersist')]
class FormWizardPersistSubjectListener
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

    public function postPersist(PostPersistEvent $event): void
    {
        $subject = $event->getSubject();
        $wizardControllerClass = $event->getSourceWizardController();
        if ($subject instanceof MultipleEntityInterface) {
            $subject = $subject->getEntitiesToPersist()[0];
        }
        $changeSet = $this->changeSets[$subject->getId()] ?? null;

        switch ($wizardControllerClass) {
            case ShareJourneyWizardController::class :
                /** @var Journey $subject */
                $sharedToIds = array_map(fn(Journey $j): ?string => $j->getId(), $subject->getSharedTo()->toArray());
                $this->metrics->log(new JourneySharedEvent($subject, $sharedToIds));
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
                $changeSet
                    ? $this->metrics->log(new JourneyEditEvent($subject, $changeSet))
                    : $this->metrics->log(new JourneyPersistEvent($subject));
                return;

            case StageWizardController::class :
                $changeSet
                    ? $this->metrics->log(new StageEditEvent($subject, $changeSet))
                    : $this->metrics->log(new StagePersistEvent($subject));
                return;

            // It's some other wizard that doesn't require metrics logging
        }
    }
}
