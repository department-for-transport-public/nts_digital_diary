<?php

namespace App\Controller\OnBoarding;

use App\Entity\OtpUser;
use App\Form\OnBoarding\OtpLoginType;
use App\Repository\InterviewerRepository;
use App\Security\OneTimePassword\InMemoryOtpUser;
use App\Security\OneTimePassword\InterviewerLinkedTrainingUserProvider;
use App\Security\Voter\Onboarding\OnboardingVoter;
use App\Utility\Security\TranslatedAuthenticationUtils;
use Exception;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class CoreController extends AbstractController
{
    #[Route(name: 'dashboard')]
    #[Template('on_boarding/core/index.html.twig')]
    public function index(AuthorizationCheckerInterface $authorizationChecker): Response
    {
        $user = $this->getUser();

        /** @var $user OtpUser */
        if ($authorizationChecker->isGranted(OnboardingVoter::ONBOARDING_EDIT)) {
            if (!$user->getHousehold() || !$user->getHousehold()->getId()) {
                return $this->redirectToRoute('onboarding_household_index');
            }

            if ($authorizationChecker->isGranted(InMemoryOtpUser::ROLE_DIRECT_ON_BOARDING_TRAINING)) {
                throw new AccessDeniedHttpException();
            }

            return $this->render('on_boarding/core/index.html.twig');
        }

        return $this->render('on_boarding/submit/confirmed.html.twig');
    }

    #[Route(path: '/login', name: 'login')]
    public function login(?UserInterface $user, TranslatedAuthenticationUtils $translatedAuthenticationUtils, InterviewerRepository $interviewerRepository, Request $request): Response
    {
        if ($user !== null) {
            return $this->redirectToRoute('onboarding_dashboard');
        }

        $trainingInterviewer = $interviewerRepository->find(
            $request->get(InterviewerLinkedTrainingUserProvider::INTERVIEWER_ID_QUERY_PARAM, '')
        );

        $form = $this->createForm(OtpLoginType::class, [
            'identifier' => $translatedAuthenticationUtils->getLastUsername('_onboarding'),
        ]);

        $errorMessage = $translatedAuthenticationUtils->getLastAuthenticationErrorMessage('security.otp');

        if ($errorMessage) {
            $form->get('group')->addError(new FormError($errorMessage));
        }


        return $this->render('on_boarding/base_form.html.twig', [
            'translation_prefix' => 'login',
            'form' => $form,
            // for training purposes
            'trainingInterviewer' => $trainingInterviewer,
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/logout', name: 'logout')]
    public function logout(): void
    {
        throw new Exception("Don't forget to activate logout in security.yaml");
    }
}