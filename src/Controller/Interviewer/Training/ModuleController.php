<?php

namespace App\Controller\Interviewer\Training;

use App\Entity\InterviewerTrainingRecord;
use App\Repository\InterviewerTrainingRecordRepository;
use App\Security\OneTimePassword\PasscodeGenerator;
use App\Security\OneTimePassword\InterviewerLinkedTrainingUserProvider;
use App\Utility\ConfirmAction\Interviewer\RetakeTrainingModuleConfirmAction;
use App\Utility\InterviewerTraining\InterviewerTrainingHelper;
use App\Utility\Security\UrlSigner;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\Registry;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/training/module/{moduleName}', name: 'training_module_')]
class ModuleController extends AbstractController
{
    public const ONBOARDING_TRAINING_LINK_TIMEOUT = 60 * 60;

    public function __construct(
        EntityManagerInterface        $entityManager,
        InterviewerTrainingHelper     $trainingHelper,
        Security                      $security,
        protected UrlSigner           $urlSigner,
        protected PasscodeGenerator   $passcodeGenerator,
        protected Registry            $workflowRegistry,
        protected TranslatorInterface $translator,
    ) {
        parent::__construct($entityManager, $trainingHelper, $security);
    }

    #[Route(name: 'index')]
    public function index(
        InterviewerTrainingRecordRepository $trainingRecordRepository,
        #[MapEntity(expr: 'repository.findLatestByModuleName(moduleName)')]
        InterviewerTrainingRecord $trainingRecord
    ): Response
    {
        $interviewer = $this->getInterviewer();
        $trainingRecords = $trainingRecordRepository->findLatestForInterviewer($interviewer);
        $nextTrainingRecord = $trainingRecords[array_search($trainingRecord, $trainingRecords) + 1] ?? null;

        return $this->render(
            "interviewer/training/module.html.twig",
            array_merge($this->getViewDataForModule($trainingRecord), [
                'interviewer' => $interviewer,
                'trainingRecord' => $trainingRecord,
                'nextTrainingRecord' => $nextTrainingRecord,
            ])
        );
    }

    #[Route('/retake', name: 'retake')]
    #[Template('interviewer/training/confirm-retake.html.twig')]
    public function retakeModule(
        RetakeTrainingModuleConfirmAction $confirmAction,
        Request $request,
        #[MapEntity(expr: 'repository.findLatestByModuleName(moduleName)')]
        InterviewerTrainingRecord $trainingRecord
    ): RedirectResponse|array
    {
        return $confirmAction
            ->setSubject($trainingRecord)
            ->controller(
                $request,
                $this->generateUrl('interviewer_training_module_index', ['moduleName' => $trainingRecord->getModuleName()])
            );
    }

    #[Route('/start', name: 'start', methods: ["POST"])]
    public function startModule(
        #[MapEntity(expr: 'repository.findLatestByModuleName(moduleName)')]
        InterviewerTrainingRecord $trainingRecord
    ): Response
    {
        return $this->attemptWorkflowTransition($trainingRecord, InterviewerTrainingRecord::TRANSITION_START);
    }

    #[Route('/complete', name: 'complete', methods: ["POST"])]
    public function completeModule(
        #[MapEntity(expr: 'repository.findLatestByModuleName(moduleName)')]
        InterviewerTrainingRecord $trainingRecord
    ): Response
    {
        return $this->attemptWorkflowTransition($trainingRecord, InterviewerTrainingRecord::TRANSITION_COMPLETE);
    }

    #[Route('/access', name: 'access')]
    public function accessModuleRedirect(
        #[MapEntity(expr: 'repository.findLatestByModuleName(moduleName)')]
        InterviewerTrainingRecord $trainingRecord
    ): Response
    {
        $url = match ($trainingRecord->getModuleName()) {
            InterviewerTrainingRecord::MODULE_PERSONAL_TRAVEL_DIARY => $this->generateUrl('app_home', [
                "_switch_user" => $trainingRecord->getInterviewer()->getTrainingPersonalDiaryKeeper()->getUser()->getUserIdentifier(),
            ]),

            InterviewerTrainingRecord::MODULE_MANUAL_ONBOARDING_PRACTICE => $this->urlSigner->sign(
                $this->generateUrl(
                    'onboarding_login',
                    [InterviewerLinkedTrainingUserProvider::INTERVIEWER_ID_QUERY_PARAM => $trainingRecord->getInterviewer()->getId()]
                ),
                self::ONBOARDING_TRAINING_LINK_TIMEOUT
            ),

            InterviewerTrainingRecord::MODULE_DIARY_CORRECTION => $this->generateUrl('interviewer_dashboard_household', [
                'household' => $trainingRecord->getHousehold()->getId(),
            ]),

            default => false
        };

        if (!$url) {
            throw new NotFoundHttpException();
        }

        return new RedirectResponse($url);
    }

    protected function attemptWorkflowTransition(InterviewerTrainingRecord $trainingRecord, string $transition): Response
    {
        $workflow = $this->workflowRegistry->get($trainingRecord);
        if ($workflow->can($trainingRecord, $transition)) {
            $workflow->apply($trainingRecord, $transition);
            $this->entityManager->flush();
        }
        return new JsonResponse([
            'newState' => [
                'text' => $this->translator->trans('training.state.label', ['state' => $trainingRecord->getState()], 'interviewer'),
                'color' => $this->translator->trans('training.state.color', ['state' => $trainingRecord->getState()], 'interviewer'),
            ],
        ]);
    }

    protected function getViewDataForModule(InterviewerTrainingRecord $trainingRecord): array
    {
        $practicalUrl = $this->generateUrl('interviewer_training_module_access', ['moduleName' => $trainingRecord->getModuleName()]);
        return match ($trainingRecord->getModuleName()) {
            InterviewerTrainingRecord::MODULE_PERSONAL_TRAVEL_DIARY,
            InterviewerTrainingRecord::MODULE_DIARY_CORRECTION => [
                'practicalUrl' => $practicalUrl,
            ],

            InterviewerTrainingRecord::MODULE_MANUAL_ONBOARDING_PRACTICE => [
                'practicalUrl' => $practicalUrl,
                'passcode1' => InterviewerLinkedTrainingUserProvider::USER_IDENTIFIER,
                'passcode2' => $this->passcodeGenerator->getPasswordForUserIdentifier(InterviewerLinkedTrainingUserProvider::USER_IDENTIFIER),
            ],

            default => []
        };
    }
}
