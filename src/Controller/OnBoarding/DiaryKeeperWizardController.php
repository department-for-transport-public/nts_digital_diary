<?php

namespace App\Controller\OnBoarding;

use App\Controller\FormWizard\AbstractSessionStateFormWizardController;
use App\Entity\DiaryKeeper;
use App\Entity\OtpUser;
use App\FormWizard\FormWizardManager;
use App\FormWizard\FormWizardStateInterface;
use App\FormWizard\OnBoarding\DiaryKeeperState;
use App\FormWizard\Place;
use App\FormWizard\PropertyMerger;
use App\Repository\DiaryKeeperRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

/**
 * @Route("/diary-keeper", name="diarykeeper_")
 * @IsGranted("ONBOARDING_EDIT")
 */
class DiaryKeeperWizardController extends AbstractSessionStateFormWizardController
{
    private ?string $diaryKeeperId;
    private DiaryKeeperRepository $diaryKeeperRepository;

    public function __construct(DiaryKeeperRepository $diaryKeeperRepository, FormWizardManager $formWizardManager, RequestStack $requestStack, PropertyMerger $propertyMerger)
    {
        $this->diaryKeeperRepository = $diaryKeeperRepository;
        parent::__construct($formWizardManager, $requestStack, $propertyMerger);
    }

    /**
     * @Route("/add/{place}", name="add")
     * @Route("/{diaryKeeperId}/edit/{place}", name="edit", requirements={"diaryKeeperId" = "\w{26}"})
     * @throws ExceptionInterface
     */
    public function index(Request $request, ?string $diaryKeeperId = null, ?string $place = null): Response
    {
        $this->diaryKeeperId = $diaryKeeperId;
        return $this->doWorkflow($request, $place);
    }

    protected function getState(): FormWizardStateInterface
    {
        /** @var OtpUser $user */
        $user = $this->getUser();

        /** @var DiaryKeeperState $state */
        $state = $this->session->get($this->getSessionKey(), new DiaryKeeperState());

        if ($this->diaryKeeperId) {
            $baseEntity = $this->diaryKeeperRepository->findOneBy(['id' => $this->diaryKeeperId, 'household' => $user->getHousehold()]);

            if (!$baseEntity) {
                throw new AccessDeniedHttpException();
            }
        } else {
            $baseEntity = new DiaryKeeper();
        }

        $user->getHousehold()->addDiaryKeeper($baseEntity);

        return $state->setSubject($this->propertyMerger->merge($baseEntity, $state->getSubject(), [
            /* details form */  'name', 'number', 'isAdult',
            /* identity form */ 'user', '?user.username', 'proxies',
        ]));
    }

    protected function getRedirectResponse(?Place $place): RedirectResponse
    {
        return $this->diaryKeeperId
            ? new RedirectResponse($this->generateUrl('onboarding_diarykeeper_edit',['diaryKeeperId' => $this->diaryKeeperId, 'place' => strval($place)]))
            : new RedirectResponse($this->generateUrl('onboarding_diarykeeper_add',['place' => strval($place)]))
        ;
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        return new RedirectResponse($this->generateUrl('onboarding_dashboard'));
    }
}