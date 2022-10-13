<?php

namespace App\Controller\OnBoarding;

use App\Controller\FormWizard\AbstractSessionStateFormWizardController;
use App\Entity\Household;
use App\Entity\OtpUser;
use App\FormWizard\FormWizardStateInterface;
use App\FormWizard\OnBoarding\HouseholdState;
use App\FormWizard\Place;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

/**
 * @Route("/household", name="household_")
 * @IsGranted("ONBOARDING_EDIT")
 */
class HouseholdWizardController extends AbstractSessionStateFormWizardController
{
    /**
     * @Route("/{place}", name="index")
     * @throws ExceptionInterface
     */
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

        /** @var OtpUser $user */
        $user = $this->getUser();

        /** @var HouseholdState $state */
        $state = $this->session->get($this->getSessionKey(), new HouseholdState());
        $baseEntity = $user->getHousehold() ?? new Household();
        $user->getAreaPeriod()->addHousehold($baseEntity);
        $user->setHousehold($baseEntity);

        return $state->setSubject($this->propertyMerger->merge($baseEntity, $state->getSubject(), [
            /* household form */      'addressNumber', 'householdNumber', 'diaryWeekStartDate',
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