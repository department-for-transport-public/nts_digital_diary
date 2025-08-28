<?php

namespace App\ListPage\Admin;

use App\ListPage\Admin\Field\HouseholdSerialFilter;
use App\Repository\HouseholdRepository;
use Doctrine\ORM\QueryBuilder;
use Ghost\GovUkCoreBundle\ListPage\AbstractListPage;
use Ghost\GovUkCoreBundle\ListPage\Field\Simple;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

class HouseholdListPage extends AbstractListPage
{

    public function __construct(protected HouseholdRepository $repository, FormFactoryInterface $formFactory, RouterInterface $router)
    {
        parent::__construct($formFactory, $router);
    }

    protected function getFieldsDefinition(): array
    {
        return [
            (new HouseholdSerialFilter('Serial/ID', 'areaPeriod.area'))->sortable(),
            (new Simple('Start date', 'household.diaryWeekStartDate'))->sortable(),
            (new Simple('State', 'household.id')),
            (new Simple("DKs\n(started/total)", 'household.diaryKeepers')),
        ];
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->repository->createQueryBuilder('household');
        return $queryBuilder
            ->select('household, areaPeriod, diaryKeepers, user')
            ->leftJoin('household.diaryKeepers', 'diaryKeepers')
            ->leftJoin('diaryKeepers.user', 'user')
            ->innerJoin('household.areaPeriod', 'areaPeriod');
    }

    protected function getDefaultOrder(): array
    {
        return [
            Simple::generateId('serial/id') => 'DESC',
        ];
    }

}
