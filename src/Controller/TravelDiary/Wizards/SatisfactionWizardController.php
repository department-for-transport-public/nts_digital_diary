<?php

namespace App\Controller\TravelDiary\Wizards;

use App\Entity\DiaryKeeper;
use App\Entity\SatisfactionSurvey;
use App\FormWizard\SatisfactionSurvey\SatisfactionSurveyState;
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

#[Route("/satisfaction-survey/{diaryKeeperId}", name: "satisfaction_survey_")]
#[IsGranted(attribute: 'ELIGIBLE_FOR_SATISFACTION_SURVEY')]
class SatisfactionWizardController extends AbstractSessionStateFormWizardController
{
    protected DiaryKeeper $diaryKeeper;

    public function __construct(
        FormWizardManager $formWizardManager,
        RequestStack $requestStack,
        PropertyMerger $propertyMerger
    ) {
        parent::__construct($formWizardManager, $requestStack, $propertyMerger);
    }

    /**
     * @throws ExceptionInterface
     */
    #[Route("", name: "start")]
    #[Route("/{place}", name: "place")]
    public function index(
        #[MapEntity(expr: "repository.find(diaryKeeperId)")]
        DiaryKeeper $diaryKeeper,
        Request $request,
        ?string $place = null): Response
    {
        $this->diaryKeeper = $diaryKeeper;
        return $this->doWorkflow($request, $place);
    }

    protected function getState(): FormWizardStateInterface
    {
        $state = $this->session->get($this->getSessionKey(), new SatisfactionSurveyState());

        $default = (new SatisfactionSurvey())
            ->setDiaryKeeper($this->diaryKeeper);

        $satisfactionSurvey = $this->propertyMerger->merge($default, $state->getSubject(), [
            'easeRating',
            'burdenRating', 'burdenReason', 'burdenReasonOther',
            'typeOfDevices', 'typeOfDevicesOther',
            'howOftenEntriesAdded',
            'writtenNoteKept',
            'preferredMethod', 'preferredMethodOther',
        ]);

        return $state->setSubject($satisfactionSurvey);
    }

    protected function getRedirectResponse(?Place $place): RedirectResponse
    {
        return new RedirectResponse($this->generateUrl('traveldiary_satisfaction_survey_place', [
            'diaryKeeperId' => $this->diaryKeeper->getId(),
            'place' => strval($place)
        ]));
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        return new RedirectResponse($this->generateUrl('traveldiary_dashboard'));
    }
}
