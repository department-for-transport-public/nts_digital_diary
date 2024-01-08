<?php

namespace App\ListPage\Admin;

use App\Entity\Feedback\Group;
use App\ListPage\Field\ChoiceFilter;
use App\ListPage\Field\DateTextFilter;
use App\Entity\Feedback\Message;
use App\ListPage\AbstractListPage;
use App\ListPage\Field\Simple;
use App\ListPage\Field\TextFilter;
use App\Repository\Feedback\MessageRepository;
use App\Security\GoogleIap\AdminRoleResolver;
use App\Security\GoogleIap\IapUser;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class FeedbackViewListPage extends AbstractListPage
{
    public function __construct(
        protected AdminRoleResolver $adminRoleResolver,
        protected MessageRepository $repository,
        FormFactoryInterface $formFactory,
        RouterInterface $router,
        protected Security $security,
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
        $isFeedbackAssigner = $this->security->isGranted('ADMIN_FEEDBACK_ASSIGN');

        $queryBuilder = $this->repository->createQueryBuilder('message');
        $queryBuilder
            ->select('message')
            ->andWhere('message.state != :state_new')
            ->setParameter('state_new', Message::STATE_NEW);

        if (!$isFeedbackAssigner) {
            $user = $this->security->getUser();

            // This will never be the case - mostly just providing type-hinting to the IDE
            if (!$user instanceof IapUser) {
                throw new NotFoundHttpException();
            }

            $assigneeName = $this->adminRoleResolver->getAssigneeNameForDomain($user->getDomain());
            if (!$assigneeName) {
                throw new NotFoundHttpException();
            }

            $queryBuilder
                ->andWhere('message.assignedTo = :assignedTo')
                ->setParameter('assignedTo', $assigneeName);
        }

        return $queryBuilder;
    }

    protected function getDefaultOrder(): array
    {
        return [
            Simple::generateId('Received') => 'DESC',
        ];
    }
}