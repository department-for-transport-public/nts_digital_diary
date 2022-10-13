<?php

namespace App\Controller\TravelDiary;

use App\Annotation\Redirect;
use App\Form\TravelDiary\NotesType;
use App\Security\ImpersonatorAuthorizationChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route("/day-{dayNumber}", name="day_", requirements={"dayNumber": "[0-7]{1}"})
 * @Redirect("is_granted('DIARY_KEEPER_WITH_APPROVED_DIARY')", route="traveldiary_dashboard")
 */
class NotesController extends AbstractController
{
    /**
     * @Route("/diary-keeper-notes", name="diary_keeper_notes")
     */
    public function diaryKeeperNotes(EntityManagerInterface $entityManager, ImpersonatorAuthorizationChecker $impersonatorAuthorizationChecker, Request $request, UserInterface $user, int $dayNumber): Response
    {
        if ($impersonatorAuthorizationChecker->isGranted('ROLE_INTERVIEWER')) {
            throw new AccessDeniedHttpException();
        }

        return $this->notes($entityManager, $request, $user, $dayNumber, 'diaryKeeperNotes', 'day.diary-keeper-notes', ['diary-keeper.notes']);
    }

    /**
     * @Route("/interviewer-notes", name="interviewer_notes")
     */
    public function interviewerNotes(EntityManagerInterface $entityManager, ImpersonatorAuthorizationChecker $impersonatorAuthorizationChecker, Request $request, UserInterface $user, int $dayNumber): Response
    {
        if (!$impersonatorAuthorizationChecker->isGranted('ROLE_INTERVIEWER')) {
            throw new AccessDeniedHttpException();
        }

        return $this->notes($entityManager, $request, $user, $dayNumber, 'interviewerNotes', 'day.interviewer-notes', ['interviewer.notes']);
    }

    protected function notes(EntityManagerInterface $entityManager, Request $request, UserInterface $user, int $dayNumber, string $field, string $translationPrefix, array $validationGroups): Response
    {
        $diaryKeeper = $this->getDiaryKeeper($user, true);
        $day = $diaryKeeper->getDiaryDayByNumber($dayNumber);

        $returnUrl = $this->generateUrl('traveldiary_dashboard_day', ['dayNumber' => $dayNumber]);

        $form = $this->createForm(NotesType::class, $day, [
            'notes_field' => $field,
            'notes_translation_parameters' => [
                'dayNumber' => $dayNumber,
            ],
            'translation_prefix' => $translationPrefix,
            'cancel_link_href' => $returnUrl,
            'validation_groups' => $validationGroups,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return new RedirectResponse($returnUrl);
        }

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $hasNotes = $propertyAccessor->getValue($day, "get${field}");

        return $this->render('travel_diary/dashboard/notes.html.twig', [
            'action' => $hasNotes ? 'edit-notes' : 'add-notes',
            'field' => $field,
            'day' => $day,
            'diaryKeeper' => $diaryKeeper,
            'form' => $form->createView(),
            'translation_prefix' => $translationPrefix,
        ]);
    }
}