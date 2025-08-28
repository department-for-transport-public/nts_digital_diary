<?php

namespace App\ListPage\Admin;

use App\Entity\Interviewer;
use App\Repository\AreaPeriodRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Ghost\GovUkCoreBundle\ListPage\AbstractListPage;
use Ghost\GovUkCoreBundle\ListPage\Field\Simple;
use Ghost\GovUkCoreBundle\ListPage\Field\TextFilter;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

class InterviewerAreaList extends AbstractListPage
{
    private Interviewer $interviewer;

    public function __construct(
        protected readonly AreaPeriodRepository $repository,
        FormFactoryInterface                    $formFactory,
        RouterInterface                         $router
    ) {
        parent::__construct($formFactory, $router);
    }

    public function setInterviewer(Interviewer $interviewer): self
    {
        $this->interviewer = $interviewer;
        return $this;
    }

    protected function getFieldsDefinition(): array
    {
        return [
            (new TextFilter('Area code', 'areaPeriod.area'))->sortable(),
            (new Simple('Year', 'areaPeriod.year'))->sortable(),
            (new Simple('Month', 'areaPeriod.month'))->sortable(),
            (new Simple('Households')),
        ];
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->repository->createQueryBuilder('areaPeriod');
        return $queryBuilder
            ->select('areaPeriod, interviewer, household')
            ->leftJoin('areaPeriod.interviewers', 'interviewer')
            ->leftJoin('areaPeriod.households', 'household', Join::WITH, 'household.isOnboardingComplete = :true')
            ->having('interviewer = :interviewer')
            ->setParameter('interviewer', $this->interviewer)
            ->setParameter('true', true);
    }

    protected function getDefaultOrder(): array
    {
        return [
            Simple::generateId('Year') => 'DESC',
            Simple::generateId('Month') => 'DESC',
            Simple::generateId('Area code') => 'ASC',
        ];
    }
}
