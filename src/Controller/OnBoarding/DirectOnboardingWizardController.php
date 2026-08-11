<?php

namespace App\Controller\OnBoarding;

use App\Entity\Household;
use App\Entity\OtpUser;
use App\Exception\RedirectResponseException;
use App\Features;
use App\Form\OnBoarding\DirectOnboardingWizard\Ingress\HouseholdType;
use App\FormWizard\OnBoarding\DirectOnboardingState;
use App\Repository\HouseholdRepository;
use App\Security\OneTimePassword\OtpUserInterface;
use App\Security\Voter\Onboarding\DirectOnboardingTrainingVoter;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkCoreBundle\Controller\FormWizard\AbstractSessionStateFormWizardController;
use Ghost\GovUkCoreBundle\FormWizard\FormWizardManager;
use Ghost\GovUkCoreBundle\FormWizard\FormWizardStateInterface;
use Ghost\GovUkCoreBundle\FormWizard\Place;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Ghost\GovUkCoreBundle\FormWizard\PropertyMerger;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

#[Route('/direct/workflow', name: "direct_workflow_")]
#[IsGranted(new Expression('!is_granted("ROLE_MANUAL_ON_BOARDING_TRAINING")'))]
class DirectOnboardingWizardController extends AbstractSessionStateFormWizardController
{
    protected Household $initialHousehold;
    protected OtpUser|UserInterface $user;
    protected ?int $diaryKeeperNumber = null;

    public function __construct(
        FormWizardManager                $formWizardManager,
        RequestStack                     $requestStack,
        PropertyMerger                   $propertyMerger,
        protected Security               $security,
        protected EntityManagerInterface $entityManager,
        protected TranslatorInterface    $translator,
        protected HouseholdRepository    $householdRepository
    ) {
        parent::__construct($formWizardManager, $requestStack, $propertyMerger);
    }

    /**
     * @throws ExceptionInterface
     */
    #[Route('', name: "index")]
    #[Route('/{place}/{diaryKeeperNumber}', name: "place")]
    public function index(Request $request, ?string $place = null, ?int $diaryKeeperNumber = null): Response
    {
        if (!$place && $response = $this->processRequest($request)) {
            return $response;
        }

        if ($place === DirectOnboardingState::STATE_DIARY_KEEPER_IDENTITY) {
            $place = new Place(DirectOnboardingState::STATE_DIARY_KEEPER_IDENTITY, ['diaryKeeperNumber' => $diaryKeeperNumber]);
        }

        $this->diaryKeeperNumber = $diaryKeeperNumber;
        return $this->doWorkflow($request, $place);
    }

    protected function processRequest(Request $request): ?Response
    {
        /** @var OtpUserInterface $user */
        $user = $this->security->getUser();
        $form = $this->createForm(HouseholdType::class, (new Household())->setAreaPeriod($user->getAreaPeriod()));

        $form->submit($request->query->all());
        $household = $form->getData();

        // User already has a household
        if ($user->getHousehold()?->getId()) {
            if (
                $household->getAddressNumber() === $user->getHousehold()->getAddressNumber()
                && $household->getHouseholdNumber() === $user->getHousehold()->getHouseholdNumber()
            ) {
                // household exists, but add# and h# in URL match existing... forward with warning
                $this->setInitialState($user->getHousehold());
                return $this->getMatchingHouseholdExistsResponse($household);
            } else {
                // details don't match, add an error and allow the validation check to handle it
                $form->addError(
                    new FormError(
                        $this->translator->trans('wizard.direct-onboarding.ingress.household-mismatch', [
                                'originalSerial' => $user->getHousehold()->getSerialNumber(),
                                'newSerial' => $household->getSerialNumber(),
                            ],
                        'validators'
                        )
                    )
                );
            }
        } else {
            // there might be a household that isn't associated with this otpUser
            if ($existingHousehold = $this->householdRepository->findBy([
                'areaPeriod' => $household->getAreaPeriod(),
                'addressNumber' => $household->getAddressNumber(),
                'householdNumber' => $household->getHouseholdNumber(),
            ])) {
                $form->addError(new FormError(
                    $this->translator->trans('wizard.direct-onboarding.ingress.existing-household', [],'validators')
                ));
            }
        }

        if (!$form->isValid()) {
            if (Features::isEnabled(Features::ALLOW_DIRECT_ONBOARDING_DEBUG) /* && $request->query->has('debug') */) {
                return $this->renderErrors([
                    'form' => $form->createView(),
                    'requestParams' => urldecode($request->getQueryString()),
                ]);
            }
            return $this->renderErrors();
        }

        $this->setInitialState($household);
        return null;
    }

    protected function setInitialState(Household $household): void
    {
        $this->initialHousehold = $household;
        $this->setState((new DirectOnboardingState())->setSubject($this->initialHousehold));
    }

    /**
     * @throws Throwable
     */
    protected function getState(): FormWizardStateInterface
    {
        /** @var OtpUserInterface $user */
        $user = $this->security->getUser();
        /** @var DirectOnboardingState $state */
        $state = $this->session->get($this->getSessionKey());
        if (!$state) {
            throw new RedirectResponseException(new RedirectResponse($this->generateUrl('onboarding_dashboard')));
        }
        $state->getSubject()->setAreaPeriod($user->getAreaPeriod());

        if ($state->getSubject()->getId() && $persistentHousehold = $this->entityManager->find(Household::class, $state->getSubject()->getId())) {
            $state->setSubject($persistentHousehold);
        } else {
            $household = $state->getSubject();
            $this->propertyMerger->mergeCollection($household, $household, 'vehicles', ['method']);
            foreach($state->getSubject()->getDiaryKeepers() as $diaryKeeper) {
                // for some reason, the user is getting set without a diarykeeper
                $diaryKeeper->getUser()?->setDiaryKeeper($diaryKeeper);

                if ($this->isGranted(DirectOnboardingTrainingVoter::DIRECT_ONBOARDING_TRAINING)) {
                    // if training, nothing is persisted, but we need to convince the proxy selection form that it is
                    // so there are some proxies to select from! However, we need to keep the same ID throughout.
                    $id = $diaryKeeper->getId();
                    $this->entityManager->persist($diaryKeeper);
                    if ($id) {
                        $refl = new \ReflectionProperty($diaryKeeper, 'id');
                        $refl->setValue($diaryKeeper, $id);
                    }
                }
            }
            $user->setHousehold($state->getSubject());
        }

        return $state;
    }

    protected function renderErrors(array $additionalViewData = []): Response
    {
        return $this->render('on_boarding/direct_onboarding/errors.html.twig', $additionalViewData);
    }

    protected function getMatchingHouseholdExistsResponse(Household $household): Response
    {
        $this->addFlash(NotificationBanner::FLASH_BAG_TYPE, new NotificationBanner(
            'direct-onboarding.ingress.resume-existing-household.notification-banner.title',
            'direct-onboarding.ingress.resume-existing-household.notification-banner.heading',
            'direct-onboarding.ingress.resume-existing-household.notification-banner.content',
            [NotificationBanner::OPTION_STYLE => NotificationBanner::STYLE_WARNING],
            ['serial' => $household->getSerialNumber()],
            'on-boarding'
        ));
        return new RedirectResponse($this->generateUrl('onboarding_direct_workflow_place', ['place' => DirectOnboardingState::STATE_INTRODUCTION]));
    }
    protected function getRedirectResponse(?Place $place): RedirectResponse
    {
        /** @var DirectOnboardingState $state */
        $state = $this->getState();
        return new RedirectResponse($this->generateUrl('onboarding_direct_workflow_place', $state->getPlaceRouteParameters($place)));
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        return null;
    }

    protected function getAbortRedirectResponse(): RedirectResponse
    {
        return new RedirectResponse($this->generateUrl('onboarding_direct_workflow_place', ['place' => DirectOnboardingState::STATE_INTRODUCTION]));
    }
}