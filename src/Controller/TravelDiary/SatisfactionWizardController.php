<?php

namespace App\Controller\TravelDiary;

use App\Controller\FormWizard\AbstractSessionStateFormWizardController;
use App\Entity\DiaryKeeper;
use App\Entity\SatisfactionSurvey;
use App\FormWizard\FormWizardManager;
use App\FormWizard\FormWizardStateInterface;
use App\FormWizard\Place;
use App\FormWizard\PropertyMerger;
use App\FormWizard\SatisfactionSurvey\SatisfactionSurveyState;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

#[Route("/satisfaction-survey/{diaryKeeperId}", name: "satisfaction_survey_")]
#[Entity("diaryKeeper", "repository.find(diaryKeeperId)")]
#[Security("is_granted('ELIGIBLE_FOR_SATISFACTION_SURVEY')")]
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