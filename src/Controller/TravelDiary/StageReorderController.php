<?php

namespace App\Controller\TravelDiary;

use App\Annotation\Redirect;
use App\Entity\Journey\Journey;
use App\Entity\Journey\Stage;
use App\Form\ConfirmActionType;
use App\Repository\DiaryKeeperRepository;
use App\Security\Voter\TravelDiary\DiaryAccessVoter;
use App\Utility\ReorderUtils;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route("/journey/{journeyId}", name="journey_")
 * @Entity("journey", expr="repository.find(journeyId)")
 * @IsGranted(DiaryAccessVoter::ACCESS, subject="journey", statusCode=404)
 * @Redirect("!is_granted('EDIT_DIARY')", route="traveldiary_dashboard")
 */
class StageReorderController extends AbstractController
{
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager, DiaryKeeperRepository $diaryKeeperRepository)
    {
        $this->entityManager = $entityManager;
        parent::__construct($diaryKeeperRepository);
    }

    /**
     * @Route("/reorder-stages", name="reorder_stages")
     */
    public function reorder(Request $request, Journey $journey, UserInterface $user): Response
    {

        /** @var Stage[] $stages */
        $stages = $journey->getStages()->toArray();

        if (count($stages) < 2) {
            // Not enough stages to reorder
            return new RedirectResponse($this->getRedirectUrl($journey));
        }

        $mappingParam = $request->query->get('mapping', null);

        /** @var Stage[] $sortedStages */
        $sortedStages = ReorderUtils::getSortedItems($stages, $mappingParam);

        $mapping = array_map(fn(Stage $action) => $action->getNumber(), $sortedStages);

        foreach($mapping as $i => $newPosition) {
            $stages[$newPosition - 1]->setNumber($i + 1);
        }

        $form = $this->createForm(ConfirmActionType::class, null, [
            'confirm_button_options' => [
                'label' => 'stage.reorder.save',
                'translation_domain' => 'travel-diary',
            ],
            'cancel_link_options' => [
                'href' => $this->getRedirectUrl($journey),
            ],
        ]);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                $cancel = $form->get('button_group')->get('cancel');
                if ($cancel instanceof SubmitButton && $cancel->isClicked()) {
                    return $this->getRedirectResponse($journey);
                }

                if ($form->isValid()) {
                    $this->entityManager->flush();
                    return $this->getRedirectResponse($journey);
                }
            }
        }

        return $this->render("travel_diary/journey/reorder_stages.html.twig", [
            'mapping' => $mapping,
            'journey' => $journey,
            'sortedActions' => $sortedStages,
            'form' => $form->createView(),
            'diaryKeeper' => $this->getDiaryKeeper($user),
        ]);
    }

    protected function getRedirectResponse(Journey $journey): RedirectResponse
    {
        return new RedirectResponse($this->getRedirectUrl($journey));
    }

    protected function getRedirectUrl(Journey $journey): string {
        return $this->generateUrl('traveldiary_journey_view', ['journeyId' => $journey->getId()]);
    }
}