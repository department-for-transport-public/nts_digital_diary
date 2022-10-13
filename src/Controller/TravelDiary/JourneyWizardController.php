<?php

namespace App\Controller\TravelDiary;

use App\Annotation\Redirect;
use App\Controller\FormWizard\AbstractSessionStateFormWizardController;
use App\Entity\DiaryDay;
use App\Entity\Journey\Journey;
use App\Entity\User;
use App\FormWizard\FormWizardStateInterface;
use App\FormWizard\Place;
use App\FormWizard\TravelDiary\JourneyState;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

/**
 * @Route("", name="journey_wizard_")
 * @Redirect("is_granted('DIARY_KEEPER_WITH_APPROVED_DIARY')", route="traveldiary_dashboard")
 */
class JourneyWizardController extends AbstractSessionStateFormWizardController
{
    protected ?string $journeyId;
    private ?string $dayNumber;

    /**
     * @Route("/day-{dayNumber}/add-journey/{place}", name="place")
     * @Route("/day-{dayNumber}/add-journey", name="start")
     * @Route("/journey/{journeyId}/edit/{place}", name="edit")
     * @throws ExceptionInterface
     */
    public function index(Request $request, ?string $place = null, ?string $journeyId = null, ?string $dayNumber = null): Response
    {
        $this->journeyId = $journeyId;
        $this->dayNumber = $dayNumber;
        
        return $this->doWorkflow($request, $place);
    }

    protected function getState(): FormWizardStateInterface
    {
        $em = $this->getDoctrine()->getManager();

        /** @var JourneyState $state */
        $state = $this->session->get($this->getSessionKey(), new JourneyState());
        $baseEntity = $this->journeyId
            ? $em->find(Journey::class, $this->journeyId)
            : (new Journey());

        if (!$baseEntity->getDiaryDay()) {
            $this->getDiaryDay()->addJourney($baseEntity);
        }

        return $state->setSubject($this->propertyMerger->merge($baseEntity, $state->getSubject(), Journey::MERGE_PROPERTIES));
    }

    protected function getRedirectResponse(?Place $place): RedirectResponse
    {
        $url = $this->journeyId
            ? $this->generateUrl('traveldiary_journey_wizard_edit', ['journeyId' => $this->journeyId, 'place' => strval($place)])
            : $this->generateUrl('traveldiary_journey_wizard_place', ['dayNumber' => $this->dayNumber, 'place' => strval($place)]);

        return new RedirectResponse($url);
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        $url = $this->journeyId
            ? $this->generateUrl('traveldiary_journey_view', ['journeyId' => $this->journeyId])
            : $this->generateUrl('traveldiary_dashboard_day', ['dayNumber' => $this->dayNumber]);

        return new RedirectResponse($url);
    }

    protected function getDiaryDay(): DiaryDay
    {
        /** @var User $user */
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();

        $diaryDay = $em
            ->getRepository(DiaryDay::class)
            ->findOneBy([
                'diaryKeeper' => $user->getDiaryKeeper(),
                'number' => $this->dayNumber
            ]);
        if (!$diaryDay) {
            $user->getDiaryKeeper()->addDiaryDay($diaryDay = (new DiaryDay())->setNumber($this->dayNumber));
            $em->flush();
        }
        return $diaryDay;
    }
}