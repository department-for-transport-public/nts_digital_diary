<?php

namespace App\Tests\EventSubscriber\Feedback;

use App\Entity\Feedback\Group;
use App\Entity\Feedback\Message;
use App\Repository\Feedback\MessageRepository;
use App\Security\GoogleIap\AdminRoleResolver;
use App\Tests\Functional\AbstractWebTestCase;
use App\Utility\Feedback\NotifyHelper;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Workflow\StateMachine;

abstract class AbstractFeedbackSubscriberTest extends AbstractWebTestCase
{
    protected EntityManagerInterface $entityManager;
    protected MessageRepository $messageRepository;
    protected ReferenceRepository $referenceRepository;

    protected NotifyHelper|MockObject $notifyHelperMock;
    protected StateMachine $stateMachine;

    public function loadFixturesAndDoSetUp(array $fixtures): void
    {
        parent::setUp();

        $container = static::getContainer();

        $this->notifyHelperMock = $this
            ->getMockBuilder(NotifyHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container->set(NotifyHelper::class, $this->notifyHelperMock);

        $adminRoleResolverMock = $this
            ->createPartialMock(AdminRoleResolver::class, ['getAssignees', 'getAssigners']);

        $adminRoleResolverMock
            ->method('getAssignees')
            ->willReturn([
                new Group('someone', 'someone.com', ['someone@example.com']),
                new Group('new-person', 'new-person.com', ['new-person@example.com']),
                new Group('original', 'original.com', ['original@example.com']),
            ]);
        $adminRoleResolverMock
            ->method('getAssigners')
            ->willReturn([
                new Group('assigner', 'assigner.com', ['assigner@example.com']),
            ]);

        $container->set(AdminRoleResolver::class, $adminRoleResolverMock);

        $databaseTool = $container->get(DatabaseToolCollection::class)->get();
        $executor = $databaseTool->loadFixtures($fixtures);

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->messageRepository = $this->entityManager->getRepository(Message::class);
        $this->referenceRepository = $executor->getReferenceRepository();

        $this->stateMachine = $container->get('state_machine.feedback_message');
    }
}