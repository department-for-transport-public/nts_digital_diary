<?php

namespace App\Controller\OnBoarding;

use App\Entity\DiaryKeeper;
use App\Entity\OtpUser;
use App\FormWizard\OnBoarding\DiaryKeeperState;
use App\Repository\DiaryKeeperRepository;
use Ghost\GovUkCoreBundle\Controller\FormWizard\AbstractSessionStateFormWizardController;
use Ghost\GovUkCoreBundle\FormWizard\FormWizardManager;
use Ghost\GovUkCoreBundle\FormWizard\FormWizardStateInterface;
use Ghost\GovUkCoreBundle\FormWizard\Place;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Ghost\GovUkCoreBundle\FormWizard\PropertyMerger;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Translation\TranslatableMessage;

#[Route(path: '/diary-keeper', name: 'diarykeeper_')]
#[IsGranted('ONBOARDING_EDIT')]
#[IsGranted(new Expression('!is_granted("ROLE_DIRECT_ON_BOARDING_TRAINING")'))]
class DiaryKeeperWizardController extends AbstractSessionStateFormWizardController
{
    private ?string $diaryKeeperId = null;

    public function __construct(
        protected readonly DiaryKeeperRepository $diaryKeeperRepository,
        FormWizardManager                        $formWizardManager,
        RequestStack                             $requestStack,
        PropertyMerger                           $propertyMerger
    ) {
        parent::__construct($formWizardManager, $requestStack, $propertyMerger);
    }

    /**
     * @throws ExceptionInterface
     */
    #[Route(path: '/add/{place}', name: 'add')]
    #[Route(path: '/{diaryKeeperId}/edit/{place}', name: 'edit', requirements: ['diaryKeeperId' => '\w{26}'])]
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

        $baseEntity = $this->propertyMerger->merge($baseEntity, $state->getSubject(), [
            /* details form  */ 'name', 'number', 'isAdult',
            /* identity form */ 'user', '?user.username', 'proxies',
        ]);

        $user->getHousehold()->addDiaryKeeper($baseEntity);

        return $state->setSubject($baseEntity);
    }

    protected function getRedirectResponse(?Place $place): RedirectResponse
    {
        return $this->diaryKeeperId
            ? new RedirectResponse($this->generateUrl('onboarding_diarykeeper_edit', ['diaryKeeperId' => $this->diaryKeeperId, 'place' => strval($place)]))
            : new RedirectResponse($this->generateUrl('onboarding_diarykeeper_add', ['place' => strval($place)]));
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        return new RedirectResponse($this->generateUrl('onboarding_dashboard'));
    }

    /**
     * @param DiaryKeeperState $formWizardState
     */
    protected function handleUniqueConstraintViolation(Request $request, FormWizardStateInterface $formWizardState): RedirectResponse
    {
        $request->getSession()->getFlashBag()->add(NotificationBanner::FLASH_BAG_TYPE, new NotificationBanner(
            new TranslatableMessage('wizard.unique-constraint-violation.notification.title', [], 'messages'),
            'diary-keeper.unique-constraint-violation.notification.heading',
            'diary-keeper.unique-constraint-violation.notification.content',
            [
                NotificationBanner::OPTION_STYLE => 'error',
                'attributes' => ['id' => self::UNIQUE_CONSTRAINT_NOTIFICATION_ID]
            ],
            [
                'name' => $formWizardState->getSubject()->getName() ?? 'unknown',
            ], 'on-boarding'
        ));
        return $this->getCancelRedirectResponse();
    }
}
