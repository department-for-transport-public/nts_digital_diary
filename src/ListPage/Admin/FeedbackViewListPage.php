<?php

namespace App\ListPage\Admin;

use App\Entity\Feedback\CategoryEnum;
use App\Entity\Feedback\Group;
use App\ListPage\Field\ChoiceFilter;
use App\ListPage\Field\DateTextFilter;
use App\Entity\Feedback\Message;
use App\ListPage\AbstractListPage;
use App\ListPage\Field\Simple;
use App\ListPage\Field\TextFilter;
use App\Repository\Feedback\MessageRepository;
use App\Security\GoogleIap\AdminRoleResolver;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FeedbackViewListPage extends AbstractListPage
{
    public function __construct(
        protected AdminRoleResolver $adminRoleResolver,
        protected MessageRepository $repository,
        FormFactoryInterface $formFactory,
        RouterInterface $router,
        protected TranslatorInterface $translator,
    ) {
        parent::__construct($formFactory, $router);
    }

    protected function getFieldsDefinition(): array
    {
        $assignees = array_map(fn(Group $g) => $g->getName(), $this->adminRoleResolver->getAssignees());
        $assignees = array_combine($assignees, $assignees);

        $states = [Message::STATE_ASSIGNED, Message::STATE_IN_PROGRESS, Message::STATE_CLOSED];
        $states = array_combine(
            array_map(fn($k) => $this->translator->trans("feedback.view.state.labels.{$k}", [], 'admin'), $states),
            $states
        );

        return [
            (new DateTextFilter('Received', 'message.sent'))->sortable(),
            (new TextFilter('Email address', 'message.emailAddress'))->sortable(),
            (new Simple('Category', 'message.category')),
            (new ChoiceFilter('Assigned', 'message.assignedTo', $assignees))->sortable(),
            (new ChoiceFilter('State', 'message.state', $states))->sortable(),
        ];
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->repository->createQueryBuilder('message');
        return $queryBuilder
            ->select('message')
            ->andWhere('message.state != :state_new')
            ->setParameters([
                'state_new' => Message::STATE_NEW,
            ]);
    }

    protected function getDefaultOrder(): array
    {
        return [
            Simple::generateId('Received') => 'DESC',
        ];
    }
}