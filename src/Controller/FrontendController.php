<?php

namespace App\Controller;

use App\Form\UserLoginType;
use App\Utility\TranslatedAuthenticationUtils;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontendController extends AbstractController
{
    /**
     * @Route(name="app_home")
     */
    public function index(): Response
    {
        $this->redirectToDashboardIfAppropriate();
        return $this->render('home/index.html.twig', []);
    }

    /**
     * @Route("/login", name="app_login")
     */
    public function login(TranslatedAuthenticationUtils $translatedAuthenticationUtils): Response
    {
        $this->redirectToDashboardIfAppropriate();

        $form = $this->createForm(UserLoginType::class, [
            'email' => $translatedAuthenticationUtils->getLastUsername(),
        ]);

        $errorMessage = $translatedAuthenticationUtils->getLastAuthenticationErrorMessage('security.main');

        if ($errorMessage) {
            $form->get('group')->addError(new FormError($errorMessage));
        }

        return $this->render('login.html.twig', [
            'translation_prefix' => 'login',
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/logout", name="app_logout")
     * @throws Exception
     */
    public function logout(): void
    {
        throw new Exception("Don't forget to activate logout in security.yaml");
    }

    /**
     * @Route("/privacy-statement")
     * @Template("frontend/privacy-statement.html.twig")
     */
    public function privacyStatement(): array
    {
        return [];
    }

    /**
     * @Route("/accessibility-statement")
     * @Template("frontend/accessibility-statement.html.twig")
     */
    public function accessibilityStatement(): array
    {
        return [];
    }
}
