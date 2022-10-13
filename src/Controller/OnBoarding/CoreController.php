<?php

namespace App\Controller\OnBoarding;

use App\Entity\OtpUser;
use App\Form\OnBoarding\OtpLoginType;
use App\Security\Voter\OnboardingVoter;
use App\Utility\TranslatedAuthenticationUtils;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class CoreController extends AbstractController
{
    /**
     * @Route(name="dashboard")
     * @Template("on_boarding/core/index.html.twig")
     */
    public function index(AuthorizationCheckerInterface $authorizationChecker): Response
    {
        $user = $this->getUser();

        /** @var $user OtpUser */
        if ($authorizationChecker->isGranted(OnboardingVoter::ONBOARDING_EDIT)) {
            if (!$user->getHousehold()) {
                return $this->redirectToRoute('onboarding_household_index');
            }

            return $this->render('on_boarding/core/index.html.twig');
        }

        return $this->render('on_boarding/submit/confirmed.html.twig');
    }

    /**
     * @Route("/login", name="login")
     */
    public function login(?UserInterface $user, TranslatedAuthenticationUtils $translatedAuthenticationUtils): Response
    {
        if ($user !== null) {
            return $this->redirectToRoute('onboarding_dashboard');
        }

        $form = $this->createForm(OtpLoginType::class, [
            'identifier' => $translatedAuthenticationUtils->getLastUsername('_onboarding'),
        ]);

        $errorMessage = $translatedAuthenticationUtils->getLastAuthenticationErrorMessage('security.otp');

        if ($errorMessage) {
            $form->get('group')->addError(new FormError($errorMessage));
        }

        return $this->render('on_boarding/base_form.html.twig', [
            'translation_prefix' => 'login',
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/logout", name="logout")
     * @throws Exception
     */
    public function logout(): void
    {
        throw new Exception("Don't forget to activate logout in security.yaml");
    }
}