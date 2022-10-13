<?php

namespace App\Controller\TravelDiary;

use App\Annotation\Redirect;
use App\Controller\FormWizard\AbstractSessionStateFormWizardController;
use App\Entity\DiaryDay;
use App\Entity\Journey\Journey;
use App\Entity\Journey\Method;
use App\FormWizard\FormWizardManager;
use App\FormWizard\FormWizardStateInterface;
use App\FormWizard\Place;
use App\FormWizard\PropertyMerger;
use App\FormWizard\TravelDiary\RepeatJourneyState;
use App\FormWizard\TravelDiary\ShareJourneyState;
use App\Security\Voter\JourneySharingVoter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

/**
 * @Route("/journey/{journeyId}/share-journey", name="share_journey_wizard_")
 * @Redirect("is_granted('DIARY_KEEPER_WITH_APPROVED_DIARY')", route="traveldiary_dashboard")
 */
class ShareJourneyWizardController extends AbstractSessionStateFormWizardController
{
    private string $sourceJourneyId;
    private EntityManagerInterface $entityManager;
    
    protected Journey $baseEntity;

    public function __construct(FormWizardManager $formWizardManager, RequestStack $requestStack, PropertyMerger $propertyMerger, EntityManagerInterface $entityManager)
    {
        parent::__construct($formWizardManager, $requestStack, $propertyMerger);
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/{place}/{stageNumber}", name="place")
     * @Route("", name="start")
     * @throws ExceptionInterface
     */
    public function index(Request $request, string $journeyId, ?string $place = null, $stageNumber = 0): Response
    {
        $this->sourceJourneyId = $journeyId;
        $this->baseEntity = $this->getSourceJourney();

        if (!$this->isGranted(JourneySharingVoter::CAN_SHARE_JOURNEYS)) {
            return $this->redirectToRoute('traveldiary_journey_view', ['journeyId' => $this->sourceJourneyId]);
        }

        if ($place !== null) $place = new Place($place, $stageNumber > 0 ? ['stageNumber' => intval($stageNumber)] : []);

        return $this->doWorkflow($request, $place);
    }

    /**
     * @throws ORMException
     */
    protected function getState(): FormWizardStateInterface
    {
        /** @var ShareJourneyState $state */
        $state = $this->session->get($this->getSessionKey(), new ShareJourneyState());

        if ($state->getSubject())
        {
            $subject = $state->getSubject();

            $this->baseEntity = $this->propertyMerger->merge($this->baseEntity, $subject, Journey::MERGE_PROPERTIES);

            // Merge for the sharedTo journeys
            $baseSharedToIds = array_map(fn(Journey $j) => $j->getId(), $this->baseEntity->getSharedTo()->toArray());
            foreach($subject->getSharedTo() as $sharedJourney) {
                // If this journey doesn't appear on the baseEntity (i.e. it's one we're adding in this wizard), then merge it...
                if (!in_array($sharedJourney->getId(), $baseSharedToIds)) {
                    $sharedJourney = $this->propertyMerger->merge($sharedJourney, $sharedJourney, ['diaryDay']);
                    $sharedJourney = $this->propertyMerger->mergeCollection($sharedJourney, $sharedJourney, 'stages', ['method', 'vehicle']);

                    $this->baseEntity->addSharedTo($sharedJourney);
                }
            }
        }

        $state->setSubject($this->baseEntity);

        return $state;
    }

    /**
     * @throws ORMException
     */
    protected function getRedirectResponse(?Place $place): RedirectResponse
    {
        /** @var RepeatJourneyState $state */
        $state = $this->getState();
        $params = array_merge($state->getPlaceRouteParameters($place), [
            'journeyId' => $this->sourceJourneyId,
        ]);
        return new RedirectResponse($this->generateUrl('traveldiary_share_journey_wizard_place', $params));
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        return new RedirectResponse($this->generateUrl('traveldiary_journey_view', ['journeyId' => $this->sourceJourneyId]));
    }

    protected function getSourceJourney(): Journey
    {
        return $this->entityManager->find(Journey::class, $this->sourceJourneyId);
    }

}