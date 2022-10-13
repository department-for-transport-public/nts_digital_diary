<?php

namespace App\Controller\TravelDiary;

use App\Entity\DiaryKeeper;
use App\Entity\User;
use App\Repository\DiaryKeeperRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as BaseAbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\User\UserInterface;

abstract class AbstractController extends BaseAbstractController
{
    protected DiaryKeeperRepository $diaryKeeperRepository;

    public function __construct(DiaryKeeperRepository $diaryKeeperRepository)
    {
        $this->diaryKeeperRepository = $diaryKeeperRepository;
    }

    protected function getDiaryKeeper(UserInterface $user, bool $includeStages = false): DiaryKeeper
    {
        $diaryKeeper = null;
        if ($user instanceof User && $user->getDiaryKeeper()) {
            $diaryKeeper = $this->diaryKeeperRepository->findByUser($user, $includeStages);
        }

        if (!$diaryKeeper) {
            throw new NotFoundHttpException();
        }

        return $diaryKeeper;
    }
}