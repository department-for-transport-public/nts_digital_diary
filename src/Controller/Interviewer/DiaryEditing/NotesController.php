<?php

namespace App\Controller\Interviewer\DiaryEditing;

use App\Controller\Interviewer\AbstractController;
use App\Entity\DiaryKeeper;
use App\Form\TravelDiary\NotesType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route("/diary-keeper/{diaryKeeper}/notes/day-{dayNumber}", name: "diary_notes_")]
class NotesController extends AbstractController
{
    #[Route("/edit", name: "edit")]
    public function index(Request $request, DiaryKeeper $diaryKeeper, int $dayNumber)
    {
        $redirectUrl = $this->generateUrl('interviewer_household_compare_day', [
            'household' => $diaryKeeper->getHousehold()->getId(),
            'day' => $dayNumber,
        ]) . "#tab-diary-keeper-{$diaryKeeper->getNumber()}";

        $form = $this->createForm(
            NotesType::class,
            $diaryKeeper->getDiaryDayByNumber($dayNumber),
            [
                'notes_field' => 'interviewerNotes',
                'translation_domain' => 'interviewer',
                'notes_translation_parameters' => [
                    'dayNumber' => $dayNumber,
                    'name' => $diaryKeeper->getName(),
                ],
                'translation_prefix' => 'diary-editing.day-notes',
                'cancel_link_href' => $redirectUrl,
                'validation_groups' => ['interviewer.notes'],
            ]
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            return $this->redirect($redirectUrl);
        }

        return $this->render('interviewer/dashboard/diary_editing/notes.html.twig', [
            'form' => $form->createView(),
            'diaryKeeper' => $diaryKeeper,
            'diaryDay' => $diaryKeeper->getDiaryDayByNumber($dayNumber),
        ]);
    }
}