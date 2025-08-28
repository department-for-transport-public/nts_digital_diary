<?php

namespace App\Controller\Admin\HouseholdMaintenance;

use App\Form\Admin\UserLookupType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(attribute: 'ROLE_MAINTENANCE')]
class UserLookupController extends AbstractController
{
    #[Route(path: '/user-lookup', name: 'user_lookup')]
    public function lookup(Request $request, UserRepository $userRepository): Response
    {
        $form = $this->createForm(UserLookupType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $emailField = $form->get('email');
            $email = $emailField->getData();
            $user = $userRepository->loadUserByIdentifier($email);

            if ($user) {
                $diaryKeeper = $user->getDiaryKeeper();

                if ($diaryKeeper) {
                    $url = $this->generateUrl('admin_household_maintenance_details', [
                        'id' => $diaryKeeper->getHousehold()->getId()
                    ]).'#highlight=diarykeeper-row-'.$diaryKeeper->getNumber();
                    return new RedirectResponse($url);
                } else if ($user->getInterviewer()) {
                    return $this->redirectToRoute('admin_interviewers_view', ['id' => $user->getInterviewer()->getId()]);
                } else {
                    $emailField->addError(new FormError('User found, but no matching diaryKeeper or Interviewer'));
                }
            } else {
                $emailField->addError(new FormError('No such email found'));
            }
        }

        return $this->render('admin/household-maintenance/user_lookup.html.twig', [
            'form' => $form,
        ]);
    }
}
