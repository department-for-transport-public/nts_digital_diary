<?php

namespace App\Controller\OnBoarding;

use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/direct', name: "direct_auth_")]
class DirectOnboardingAuthController extends AbstractController
{
    #[Route('', name: "index")]
    public function index(Request $request): Response
    {

        // if already authenticated and there's a query string, forward to the wizard
        if ($this->getUser()?->getUserIdentifier() === $request->query->get('passcode1', false)) {
            $queryParams = $request->query->all();
            unset($queryParams['passcode1']);
            unset($queryParams['passcode2']);
            return $this->redirect($this->generateUrl('onboarding_direct_workflow_index', $queryParams));
        }

        if (!$request->query->has('passcode1') || !$request->query->has('passcode2')) {
            return $this->redirect($this->generateUrl('onboarding_login'));
        }

        // otherwise, show the form
        $form = $this->createForm(FormType::class)
            ->add('continue', ButtonType::class, [
                'label' => 'actions.continue',
            ]);

        return $this->render('on_boarding/direct_onboarding/pre-auth.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}