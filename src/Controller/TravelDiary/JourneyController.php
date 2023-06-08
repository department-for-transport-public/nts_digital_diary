<?php

namespace App\Controller\TravelDiary;

use App\Annotation\Redirect;
use App\Entity\Journey\Journey;
use App\Entity\User;
use App\Repository\Journey\JourneyRepository;
use App\Security\Voter\TravelDiary\DiaryAccessVoter;
use App\Utility\ConfirmAction\TravelDiary\DeleteJourneyConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route("/journey/{journeyId}", name="journey")
 * @Entity("journey", expr="repository.findByJourneyId(journeyId)")
 * @IsGranted(DiaryAccessVoter::ACCESS, subject="journey", statusCode=404)
 * @Redirect("!is_granted('EDIT_DIARY')", route="traveldiary_dashboard")
 */
class JourneyController extends AbstractController
{
    /**
     * @Route(name="_view")
     * @Template("travel_diary/journey/view.html.twig")
     */
    public function view(UserInterface $user, Journey $journey, JourneyRepository $journeyRepository): array
    {
        $diaryKeeper = $journey->getDiaryDay()->getDiaryKeeper();

        return [
            'diaryKeeper' => $diaryKeeper,
            'day' => $journey->getDiaryDay(),
            'journey' => $journey,
            'sharedFromName' => $journeyRepository->getSharedFromName($journey),
            'sharedToNames' => $journeyRepository->getSharedToNames($journey),
        ];
    }

    /**
     * @Route("/delete", name="_delete")
     * @Template("travel_diary/journey/delete.html.twig")
     */
    public function delete(Journey $journey, DeleteJourneyConfirmAction $confirmAction, Request $request): RedirectResponse | array
    {
        return $confirmAction
            ->setSubject($journey)
            ->controller(
                $request,
                $this->generateUrl('traveldiary_dashboard_day', ['dayNumber' => $journey->getDiaryDay()->getNumber()]),
                $this->generateUrl('traveldiary_journey_view', ['journeyId' => $journey->getId()])
            );
    }

    /**
     * @Route("/complete", name="_complete")
     */
    public function markComplete(Journey $journey): Response
    {
        $journey->setIsPartial(false);
        $entityManager->flush();

        return new RedirectResponse($this->generateUrl('traveldiary_dashboard_day', ['dayNumber' => $journey->getDiaryDay()->getNumber()]));
    }
}