<?php

namespace App\Controller\TravelDiary;

use App\Attribute\Redirect;
use App\Entity\DiaryDay;
use App\Entity\Journey\Journey;
use App\Entity\User;
use App\FormWizard\TravelDiary\JourneyState;
use App\Security\Voter\TravelDiary\DiaryAccessVoter;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkCoreBundle\Controller\FormWizard\AbstractSessionStateFormWizardController;
use Ghost\GovUkCoreBundle\FormWizard\FormWizardManager;
use Ghost\GovUkCoreBundle\FormWizard\FormWizardStateInterface;
use Ghost\GovUkCoreBundle\FormWizard\Place;
use Ghost\GovUkCoreBundle\FormWizard\PropertyMerger;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

#[Redirect("!is_granted('EDIT_DIARY')", route: "traveldiary_dashboard")]
#[Route(path: '', name: 'journey_wizard_')]
class JourneyWizardController extends AbstractSessionStateFormWizardController
{
    protected Journey $journey;
    private ?string $dayNumber = null;

    public function __construct(
        protected EntityManagerInterface $entityManager,
        FormWizardManager                $formWizardManager,
        RequestStack                     $requestStack,
        PropertyMerger                   $propertyMerger
    )
    {
        parent::__construct($formWizardManager, $requestStack, $propertyMerger);
    }

    /**
     * @throws ExceptionInterface
     */
    #[Route(path: '/day-{dayNumber}/add-journey/{place}', name: 'place', requirements: ['dayNumber' => '[0-7]{1}'])]
    #[Route(path: '/day-{dayNumber}/add-journey', name: 'start', requirements: ['dayNumber' => '[0-7]{1}'])]
    public function add(Request $request, ?string $place = null, ?string $dayNumber = null): Response
    {
        $this->journey = new Journey();
        $this->dayNumber = $dayNumber;

        return $this->doWorkflow($request, $place);
    }

    /**
     * @throws ExceptionInterface
     */
    #[Route(path: '/journey/{journeyId}/edit/{place}', name: 'edit')]
    #[IsGranted(DiaryAccessVoter::ACCESS, subject: 'journey', statusCode: 404)]
    public function edit(
        Request $request,
        #[MapEntity(expr: "repository.findByJourneyId(journeyId)")]
        Journey $journey,
        ?string $place = null,
        ?string $dayNumber = null
    ): Response
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

        $diaryDay = $this->entityManager
            ->getRepository(DiaryDay::class)
            ->findOneBy([
                'diaryKeeper' => $user->getDiaryKeeper(),
                'number' => $this->dayNumber
            ]);

        if (!$diaryDay) {
            $user->getDiaryKeeper()->addDiaryDay($diaryDay = (new DiaryDay())->setNumber($this->dayNumber));
            $this->entityManager->flush();
        }

        return $diaryDay;
    }
}
