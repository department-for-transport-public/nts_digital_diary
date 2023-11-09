<?php

namespace App\Controller\Interviewer\Training;

use App\Entity\InterviewerTrainingRecord;
use App\Security\OneTimePassword\PasscodeGenerator;
use App\Security\OneTimePassword\TrainingUserProvider;
use App\Utility\ConfirmAction\Interviewer\RetakeTrainingModuleConfirmAction;
use App\Utility\InterviewerTraining\InterviewerTrainingHelper;
use App\Utility\UrlSigner;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Workflow\Registry;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/training/module/{moduleName}', name: 'training_module_')]
#[Entity('trainingRecord', expr: 'repository.findLatestByModuleName(moduleName)')]
class ModuleController extends AbstractController
{
    public function __construct(
        EntityManagerInterface $entityManager, InterviewerTrainingHelper $trainingHelper, Security $security,
        protected UrlSigner $urlSigner,
        protected PasscodeGenerator $passcodeGenerator,
        protected Registry $workflowRegistry,
        protected TranslatorInterface $translator,
    ) {
        parent::__construct($entityManager, $trainingHelper, $security);
    }

    #[Route(name: 'index')]
    public function index(InterviewerTrainingRecord $trainingRecord): Response
    {
        $interviewer = $this->getInterviewer();
        return $this->render(
            "interviewer/training/module.html.twig",
            array_merge($this->getViewDataForModule($trainingRecord), [
                'interviewer' => $interviewer,
                'trainingHistory' => $interviewer->getTrainingRecordsForModule($trainingRecord->getModuleName()),
                'trainingRecord' => $trainingRecord,
            ])
        );
    }

    #[Route('/retake', name: 'retake')]
    #[Template('interviewer/training/confirm-retake.html.twig')]
    public function retakeModule(RetakeTrainingModuleConfirmAction $confirmAction, Request $request, InterviewerTrainingRecord $trainingRecord): RedirectResponse|array
    {
        return $confirmAction
            ->setSubject($trainingRecord)
            ->controller(
                $request,
                $this->generateUrl('interviewer_training_module_index', ['moduleName' => $trainingRecord->getModuleName()])
            );
    }

    #[Route('/start', name: 'start', methods: ["POST"])]
    public function startModule(InterviewerTrainingRecord $trainingRecord): Response
    {
        return $this->attemptWorkflowTransition($trainingRecord, InterviewerTrainingRecord::TRANSITION_START);
    }

    #[Route('/complete', name: 'complete', methods: ["POST"])]
    public function completeModule(InterviewerTrainingRecord $trainingRecord): Response
    {
        return $this->attemptWorkflowTransition($trainingRecord, InterviewerTrainingRecord::TRANSITION_COMPLETE);
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

    public function getViewDataForModule(InterviewerTrainingRecord $trainingRecord): array
    {
        return match($trainingRecord->getModuleName()) {
            InterviewerTrainingRecord::MODULE_PERSONAL_TRAVEL_DIARY => [
                'practicalUrl' => $this->generateUrl('app_home', [
                    "_switch_user" => $trainingRecord->getInterviewer()->getTrainingPersonalDiaryKeeper()->getUser()->getUserIdentifier(),
                ]),
            ],
            InterviewerTrainingRecord::MODULE_ONBOARDING_PRACTICE => [
                'practicalUrl' => $this->urlSigner->sign(
                    $this->generateUrl(
                        'onboarding_login',
                        ['_interviewer' => $trainingRecord->getInterviewer()->getId()]
                    ),
                    300
                ),
                'passcode1' => TrainingUserProvider::USER_IDENTIFIER,
                'passcode2' => $this->passcodeGenerator->getPasswordForUserIdentifier(TrainingUserProvider::USER_IDENTIFIER),
            ],
            InterviewerTrainingRecord::MODULE_DIARY_CORRECTION => [
                'practicalUrl' => $this->generateUrl('interviewer_dashboard_household', [
                    'household' => $trainingRecord->getHousehold()->getId(),
                ]),
            ],

            default => []
        };
    }
}