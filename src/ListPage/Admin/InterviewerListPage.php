<?php

namespace App\ListPage\Admin;

use App\Repository\InterviewerRepository;
use Doctrine\ORM\QueryBuilder;
use Ghost\GovUkCoreBundle\ListPage\AbstractListPage;
use Ghost\GovUkCoreBundle\ListPage\Field\Simple;
use Ghost\GovUkCoreBundle\ListPage\Field\TextFilter;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

class InterviewerListPage extends AbstractListPage
{
    public function __construct(
        protected readonly InterviewerRepository $repository,
        FormFactoryInterface                     $formFactory,
        RouterInterface                          $router,
    ) {
        parent::__construct($formFactory, $router);
    }

    protected function getFieldsDefinition(): array
    {
        return [
            (new TextFilter('Name', 'interviewer.name'))->sortable(),
            (new TextFilter('Serial/ID', 'interviewer.serialId'))->sortable(),
            (new TextFilter('Email', 'user.username'))->sortable(),
        ];
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->repository->createQueryBuilder('interviewer');
        return $queryBuilder
            ->select('interviewer, user')
            ->leftJoin('interviewer.user', 'user');
    }

    protected function getDefaultOrder(): array
    {
        return [
            Simple::generateId('name') => 'ASC',
        ];
    }
}
