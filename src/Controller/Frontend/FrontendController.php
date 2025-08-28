<?php

namespace App\Controller\Frontend;

use App\Controller\AbstractController;
use App\Form\Frontend\UserLoginType;
use App\Utility\Security\TranslatedAuthenticationUtils;
use Exception;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FrontendController extends AbstractController
{
    /**
     * To avoid problems which would occur when logging in, due to impersonation (i.e. trying to access URLs we're not
     * allowed to), the firewall is configured to always send us to the default target.
     *
     * However, in some cases redirection is needed, and that's what this little workaround is for.
     */
    public const REDIRECT_INTERVIEWER_DASHBOARD_TO_GUIDE = 'redirect-interviewer-dashboard-to-guide';

    #[Route(name: 'home')]
    public function index(): Response
    {
        $this->redirectToDashboardIfAppropriate();
        return $this->render('frontend/index.html.twig', []);
    }

    #[Route(path: '/login', name: 'login')]
    public function login(Request $request, TranslatedAuthenticationUtils $translatedAuthenticationUtils): Response
    {
        $this->setDashboardRedirectFlags($request);
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
            'form' => $form,
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

    #[Route(path: '/privacy-statement', name: 'frontend_privacystatement')]
    #[Template('frontend/privacy-statement.html.twig')]
    public function privacyStatement(): array
    {
        return [];
    }

    #[Route(path: '/accessibility-statement', name: 'frontend_accessibilitystatement')]
    #[Template('frontend/accessibility-statement.html.twig')]
    public function accessibilityStatement(): array
    {
        return [];
    }

    public function setDashboardRedirectFlags(Request $request): mixed
    {
        $session = $request->getSession();
        $originalTargetPath = $session->get('_security.main.target_path');

        $shouldSet = false;
        $path = null;

        if ($originalTargetPath) {
            $path = parse_url((string) $originalTargetPath, PHP_URL_PATH);
            $shouldSet = ($path === '/interviewer/guide.pdf')
                || str_starts_with($path, '/interviewer/docs/');
        }

        // N.B. See constant for commentary
        if ($shouldSet) {
            $session->set(self::REDIRECT_INTERVIEWER_DASHBOARD_TO_GUIDE, $path);
        } else {
            $session->remove(self::REDIRECT_INTERVIEWER_DASHBOARD_TO_GUIDE);
        }

        return $originalTargetPath;
    }
}
