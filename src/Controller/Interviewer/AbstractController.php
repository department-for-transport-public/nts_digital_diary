<?php

namespace App\Controller\Interviewer;

use App\Entity\AreaPeriod;
use App\Entity\Interviewer;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @IsGranted("ROLE_INTERVIEWER")
 */
abstract class AbstractController extends \App\Controller\AbstractController
{
    const TRANSLATION_DOMAIN = 'interviewer';

    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    protected function getInterviewer(UserInterface $user): Interviewer
    {
        $interviewer = null;
        if ($user instanceof User && $user->getInterviewer()) {
            $repository = $this->entityManager->getRepository(Interviewer::class);

            $interviewer = $repository->findOneByUser($user);
        }

        if (!$interviewer) {
            throw new NotFoundHttpException();
        }

        return $interviewer;
    }

    protected function checkInterviewerIsSubscribedToAreaPeriod(AreaPeriod $areaPeriod, Interviewer $interviewer): void {
        $areaPeriodId = $areaPeriod->getId();
        foreach($interviewer->getAreaPeriods() as $intAreaPeriod) {
            if ($intAreaPeriod->getId() === $areaPeriodId) {
                return;
            }
        }

        throw new NotFoundHttpException();
    }

    protected function checkInterviewerIsNotSubscribedToAreaPeriod(AreaPeriod $areaPeriod, Interviewer $interviewer): void {
        $areaPeriodId = $areaPeriod->getId();
        foreach($interviewer->getAreaPeriods() as $intAreaPeriod) {
            if ($intAreaPeriod->getId() === $areaPeriodId) {
                throw new NotFoundHttpException();
            }
        }
    }

    protected function redirectToAreaPeriodPage(AreaPeriod $areaPeriod): RedirectResponse
    {
        return new RedirectResponse($this->generateUrl('interviewer_dashboard_area', ['areaPeriod' => $areaPeriod->getId()]));
    }
}