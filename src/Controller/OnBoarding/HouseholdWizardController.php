<?php

namespace App\Controller\OnBoarding;

use App\Entity\Household;
use App\FormWizard\OnBoarding\HouseholdState;
use App\Repository\HouseholdRepository;
use App\Security\OneTimePassword\OtpUserInterface;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkCoreBundle\Controller\FormWizard\AbstractSessionStateFormWizardController;
use Ghost\GovUkCoreBundle\FormWizard\FormWizardManager;
use Ghost\GovUkCoreBundle\FormWizard\FormWizardStateInterface;
use Ghost\GovUkCoreBundle\FormWizard\Place;
use Ghost\GovUkCoreBundle\FormWizard\PropertyMerger;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

#[Route(path: '/household', name: 'household_')]
#[IsGranted('ONBOARDING_EDIT')]
#[IsGranted(new Expression('!is_granted("ROLE_DIRECT_ON_BOARDING_TRAINING")'))]
class HouseholdWizardController extends AbstractSessionStateFormWizardController
{
    public function __construct(
        FormWizardManager                $formWizardManager,
        RequestStack                     $requestStack,
        PropertyMerger                   $propertyMerger,
        protected EntityManagerInterface $entityManager,
        protected HouseholdRepository    $householdRepository,
    ){
        parent::__construct($formWizardManager, $requestStack, $propertyMerger);
    }

    /**
     * @throws ExceptionInterface
     */
    #[Route(path: '/{place}', name: 'index')]
    public function index(Request $request, ?string $place = null): Response
    {
        return $this->doWorkflow($request, $place);
    }

    protected function getState(): FormWizardStateInterface
    {
        static $state = null;

        if ($state) {
            return $state;
        }

        /** @var OtpUserInterface $user */
        $user = $this->getUser();

        /** @var HouseholdState $state */
        $state = $this->session->get($this->getSessionKey(), new HouseholdState());
        $baseEntity = $user->getHousehold() ?? new Household();
        $user->getAreaPeriod()->addHousehold($baseEntity);
        $user->setHousehold($baseEntity);

        return $state->setSubject($this->propertyMerger->merge($baseEntity, $state->getSubject(), [
            /* household form */ 'addressNumber', 'householdNumber', 'diaryWeekStartDate',
        ]));
    }

    protected function getRedirectResponse(?Place $place): RedirectResponse
    {
        return new RedirectResponse($this->generateUrl('onboarding_household_index', ['place' => strval($place)]));
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        /** @var null|HouseholdState $state */
        $state = $this->getState();

        return $state->getSubject()->getId() !== null ?
            new RedirectResponse($this->generateUrl('onboarding_dashboard')) :
            null;
    }
}