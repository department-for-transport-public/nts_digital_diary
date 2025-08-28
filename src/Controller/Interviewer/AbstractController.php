<?php

namespace App\Controller\Interviewer;

use App\Entity\Interviewer;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[\Symfony\Component\Security\Http\Attribute\IsGranted('ROLE_INTERVIEWER')]
abstract class AbstractController extends \App\Controller\AbstractController
{
    public const TRANSLATION_DOMAIN = 'interviewer';

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected Security               $security,
    ) {}

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