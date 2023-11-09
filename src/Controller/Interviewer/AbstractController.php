<?php

namespace App\Controller\Interviewer;

use App\Entity\AreaPeriod;
use App\Entity\Interviewer;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @IsGranted("ROLE_INTERVIEWER")
 */
abstract class AbstractController extends \App\Controller\AbstractController
{
    const TRANSLATION_DOMAIN = 'interviewer';

    protected EntityManagerInterface $entityManager;
    protected Security $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    protected function getInterviewer(): Interviewer
    {
        $user = $this->security->getUser();
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
}