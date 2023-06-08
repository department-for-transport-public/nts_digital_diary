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
use App\Security\Voter\TravelDiary\DiaryAccessVoter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

/**
 * @Route("", name="journey_wizard_")
 * @Redirect("!is_granted('EDIT_DIARY')", route="traveldiary_dashboard")
 */
class JourneyWizardController extends AbstractSessionStateFormWizardController
{
    protected Journey $journey;
    private ?string $dayNumber;

    /**
     * @Route("/day-{dayNumber}/add-journey/{place}", name="place")
     * @Route("/day-{dayNumber}/add-journey", name="start")
     * @throws ExceptionInterface
     */
    public function add(Request $request, ?string $place = null, ?string $dayNumber = null): Response
    {
        $this->journey = new Journey();
        $this->dayNumber = $dayNumber;
        
        return $this->doWorkflow($request, $place);
    }

    /**
     * @Route("/journey/{journeyId}/edit/{place}", name="edit")
     * @Entity("journey", expr="repository.findByJourneyId(journeyId)")
     * @IsGranted(DiaryAccessVoter::ACCESS, subject="journey", statusCode=404)
     * @throws ExceptionInterface
     */
    public function edit(Request $request, Journey $journey, ?string $place = null, ?string $dayNumber = null): Response
    {
        $this->journey = $journey;
        $this->dayNumber = $dayNumber;

        return $this->doWorkflow($request, $place);
    }

    protected function getState(): FormWizardStateInterface
    {
        /** @var JourneyState $state */
        $state = $this->session->get($this->getSessionKey(), new JourneyState());

        if (!$this->journey->getDiaryDay()) {
            $this->getDiaryDay()->addJourney($this->journey);
        }

        return $state->setSubject($this->propertyMerger->merge($this->journey, $state->getSubject(), Journey::MERGE_PROPERTIES));
    }

    protected function getRedirectResponse(?Place $place): RedirectResponse
    {
        $url = $this->journey->getId()
            ? $this->generateUrl('traveldiary_journey_wizard_edit', ['journeyId' => $this->journey->getId(), 'place' => strval($place)])
            : $this->generateUrl('traveldiary_journey_wizard_place', ['dayNumber' => $this->dayNumber, 'place' => strval($place)]);

        return new RedirectResponse($url);
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        $url = $this->journey->getId()
            ? $this->generateUrl('traveldiary_journey_view', ['journeyId' => $this->journey->getId()])
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