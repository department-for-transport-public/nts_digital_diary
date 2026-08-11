<?php

namespace App\Controller\TravelDiary\Wizards\CombinedReflow;

use App\Entity\Journey\Journey;
use App\FormWizard\TravelDiary\CombinedReflowState;
use App\Utility\DateTimeFormats;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Ghost\GovUkCoreBundle\FormWizard\FormWizardManager;
use Ghost\GovUkCoreBundle\FormWizard\Metadata\PlaceMetadata;
use Ghost\GovUkCoreBundle\FormWizard\Place;
use Ghost\GovUkCoreBundle\FormWizard\PropertyMerger;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/reflow/day-{dayNumber}/new-add-journey', name: 'reflow_add_journey_wizard_', requirements: ['dayNumber' => '[0-7]{1}'])]
class AddJourneyController extends AbstractCombinedReflowController
{
    public function __construct(
        EntityManagerInterface $entityManager,
        FormWizardManager $formWizardManager,
        RequestStack $requestStack,
        PropertyMerger $propertyMerger,
    ) {
        parent::__construct($entityManager, $formWizardManager, $requestStack, $propertyMerger);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/{place}/{stageNumber}', name: 'place')]
    #[Route(path: '', name: 'start')]
    public function add(Request $request, ?string $dayNumber = null, ?string $place = null, $stageNumber = 0): Response
    {
        $this->dayNumber = $dayNumber;
        if ($place !== null) $place = new Place($place, $stageNumber > 0 ? ['stageNumber' => intval($stageNumber)] : []);
        return $this->doWorkflow($request, $place, $this->getAdditionalViewData(...));
    }

    protected function getRedirectResponse(?Place $place): RedirectResponse
    {
        if ($bounceBack = $this->bounceBackRedirectResponse($place)) {
            return $bounceBack;
        }

        $state = $this->getState();
        $params = array_merge(['dayNumber' => $this->dayNumber], $state->getPlaceRouteParameters($place));
        $url = $this->generateUrl('traveldiary_reflow_add_journey_wizard_place', $params);
        return new RedirectResponse($url);
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        $url = $this->generateUrl('traveldiary_dashboard_day', ['dayNumber' => $this->dayNumber]);
        return new RedirectResponse($url);
    }
}