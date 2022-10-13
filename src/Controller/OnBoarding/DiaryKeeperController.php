<?php

namespace App\Controller\OnBoarding;

use App\Entity\DiaryKeeper;
use App\Utility\ConfirmAction\OnBoarding\DeleteDiaryKeeperConfirmAction;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * @Route("/diary-keeper", name="diarykeeper_")
 * @IsGranted("ONBOARDING_EDIT")
 */
class DiaryKeeperController extends AbstractController
{
    /**
     * @Route("/{diaryKeeperId}/delete", name="delete", requirements={"diaryKeeperId" = "\w{26}"})
     * @ParamConverter("diaryKeeper", options={"id" = "diaryKeeperId"})
     */
    public function delete(Request $request, DiaryKeeper $diaryKeeper, DeleteDiaryKeeperConfirmAction $confirmAction): Response
    {
        $this->checkIsCorrectHousehold($diaryKeeper->getHousehold());

        $inaccessibleDiaryKeepers = $diaryKeeper->whichDiaryKeepersWouldBeInaccessibleIfDeleted();

        if (count($inaccessibleDiaryKeepers) > 0) {
            $flashBag = $request->getSession()->getFlashBag();
            $flashBag->add(NotificationBanner::FLASH_BAG_TYPE, $this->getErrorBanner($diaryKeeper, $inaccessibleDiaryKeepers));
            return new RedirectResponse($this->generateUrl('onboarding_dashboard'));
        }

        $responseOrData = $confirmAction
            ->setSubject($diaryKeeper)
            ->controller($request,$this->generateUrl('onboarding_dashboard')."#tab-diary-keeper-{$diaryKeeper->getNumber()}");

        return (is_array($responseOrData)) ?
            $this->render('on_boarding/diary_keeper/delete.html.twig', $responseOrData) :
            $responseOrData;
    }

    public function getErrorBanner(DiaryKeeper $diaryKeeper, array $inaccessibleDiaryKeepers): NotificationBanner
    {
        $content = $this->renderView('on_boarding/diary_keeper/delete_inaccessible_banner.html.twig', [
            'diaryKeeper' => $diaryKeeper,
            'inaccessibleDiaryKeepers' => $inaccessibleDiaryKeepers,
        ]);

        return new NotificationBanner(
            new TranslatableMessage('notification.warning'),
            "diary-keeper.delete.warning-notification.heading",
            $content,
            ['style' => NotificationBanner::STYLE_WARNING, 'html_content' => true],
            [],
            'on-boarding',
        );
    }
}