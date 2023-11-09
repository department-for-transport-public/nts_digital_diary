<?php

namespace App\ListPage\Admin;

use App\Entity\Feedback\Message;
use App\ListPage\AbstractListPage;
use App\ListPage\Field\Simple;
use App\ListPage\Field\TextFilter;
use App\Repository\Feedback\MessageRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

class FeedbackAssignmentListPage extends AbstractListPage
{
    public function __construct(
        protected MessageRepository $repository,
        FormFactoryInterface $formFactory,
        RouterInterface $router
    ) {
        parent::__construct($formFactory, $router);
    }

    protected function getFieldsDefinition(): array
    {
        return [
            (new TextFilter('Received', 'message.sent', [], ['replace_slashes_with_dashes' => true]))->sortable(),
            (new TextFilter('Email address', 'message.emailAddress'))->sortable(),
            (new Simple('Category', 'message.category')),
        ];
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->repository->createQueryBuilder('message');
        return $queryBuilder
            ->select('message')
            ->where('message.state = :state')
            ->setParameter('state', Message::STATE_NEW)
            ;
    }

    protected function getDefaultOrder(): array
    {
        return [
            Simple::generateId('Received') => 'DESC',
        ];
    }
}