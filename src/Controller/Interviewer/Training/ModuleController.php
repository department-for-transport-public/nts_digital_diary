<?php

namespace App\Controller\Interviewer\Training;

use App\Entity\InterviewerTrainingRecord;
use App\Security\OneTimePassword\PasscodeGenerator;
use App\Security\OneTimePassword\TrainingUserProvider;
use App\Security\Voter\Interviewer\TrainingModuleVoter;
use App\Utility\ConfirmAction\Interviewer\RetakeTrainingModuleConfirmAction;
use App\Utility\InterviewerTraining\InterviewerTrainingHelper;
use App\Utility\UrlSigner;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

#[Route('/training/module/{moduleName}', name: 'training_module_')]
#[Entity('trainingRecord', expr: 'repository.findLatestByModuleName(moduleName)')]
class ModuleController extends AbstractController
{
    private UrlSigner $urlSigner;
    private PasscodeGenerator $passcodeGenerator;

    public function __construct(EntityManagerInterface $entityManager, InterviewerTrainingHelper $trainingHelper, Security $security, UrlSigner $urlSigner, PasscodeGenerator $passcodeGenerator)
    {
        parent::__construct($entityManager, $trainingHelper, $security);
        $this->urlSigner = $urlSigner;
        $this->passcodeGenerator = $passcodeGenerator;
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
    #[IsGranted(TrainingModuleVoter::CAN_START, subject: 'trainingRecord')]
    public function startModule(InterviewerTrainingRecord $trainingRecord): RedirectResponse
    {
        $trainingRecord->setStartedAt(new DateTimeImmutable());
        $this->entityManager->flush();
        return $this->redirect($this->generateUrl('interviewer_training_module_index', ['moduleName' => $trainingRecord->getModuleName()]));
    }

    #[Route('/complete', name: 'complete', methods: ["POST"])]
    #[IsGranted(TrainingModuleVoter::CAN_COMPLETE, subject: 'trainingRecord')]
    public function completeModule(InterviewerTrainingRecord $trainingRecord): RedirectResponse
    {
        $trainingRecord->setCompletedAt(new DateTimeImmutable());
        $this->entityManager->flush();
        return $this->redirect($this->generateUrl('interviewer_training_module_index', ['moduleName' => $trainingRecord->getModuleName()]));
    }


    public function getViewDataForModule(InterviewerTrainingRecord $trainingRecord): array
    {
        return match($trainingRecord->getModuleName()) {
            InterviewerTrainingRecord::MODULE_PERSONAL_TRAVEL_DIARY => [
                'practicalUrl' => $this->generateUrl('app_home', [
                    "_switch_user" => $trainingRecord->getInterviewer()->getTrainingPersonalDiaryKeeper()->getUser()->getUserIdentifier(),
                ]),
            ],
            InterviewerTrainingRecord::MODULE_ONBOARDING => [
                'practicalUrl' => $this->urlSigner->sign(
                    $this->generateUrl(
                        'onboarding_login',
                        ['_interviewer' => $trainingRecord->getInterviewer()->getId()]
                    ),
                    300
                ),
                'passcode' => $this->passcodeGenerator->getPasswordForUserIdentifier(TrainingUserProvider::USER_IDENTIFIER),
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