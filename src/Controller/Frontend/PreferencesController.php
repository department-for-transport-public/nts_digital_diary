<?php

namespace App\Controller\Frontend;

use App\Controller\AbstractController;
use App\Entity\PreferenceUserInterface;
use App\Entity\User;
use App\Form\Frontend\UserPreferencesType;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/user/preferences', name: 'user_preferences_')]
class PreferencesController extends AbstractController
{
    #[Route(name: 'index')]
    #[IsGranted(attribute: new Expression("(not is_granted('IS_IMPERSONATOR')) and (is_granted('ROLE_DIARY_KEEPER') or is_granted('ROLE_INTERVIEWER'))"))]
    public function index(
        Request                $request,
        Security               $security,
        EntityManagerInterface $entityManager
    ): Response
    {
        /** @var PreferenceUserInterface|User $user */
        $user = $security->getUser();
        $form = $this->createForm(UserPreferencesType::class, $user->getPreferences());
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $request->getSession()->getFlashBag()->add(
                NotificationBanner::FLASH_BAG_TYPE,
                $this->getNotification()
            );
            return new RedirectResponse($this->generateUrl('app_home'));
        }

        return $this->render('frontend/user_preferences.html.twig', [
            'form' => $form,
            'name' => $user->getDiaryKeeper()?->getName() ?? $user->getInterviewer()?->getName(),
        ]);
    }

    protected function getNotification(): NotificationBanner
    {
        return new NotificationBanner(
            new TranslatableMessage('user-preferences.save.notification-banner.title'),
            new TranslatableMessage('user-preferences.save.notification-banner.heading'),
            new TranslatableMessage('user-preferences.save.notification-banner.content'),
        );
    }
}