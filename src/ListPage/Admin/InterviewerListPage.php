<?php

namespace App\ListPage\Admin;

use App\ListPage\AbstractListPage;
use App\ListPage\Field\Simple;
use App\ListPage\Field\TextFilter;
use App\Repository\InterviewerRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

class InterviewerListPage extends AbstractListPage
{
    private InterviewerRepository $repository;

    public function __construct(InterviewerRepository $repository, FormFactoryInterface $formFactory, RouterInterface $router)
    {
        parent::__construct($formFactory, $router);
        $this->repository = $repository;
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