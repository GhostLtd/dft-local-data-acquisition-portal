<?php

namespace App\Tests\EventSubscriber;

use App\Entity\FundReturn\FundReturn;
use App\Entity\User;
use App\EventSubscriber\SignoffSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;

class SignoffSubscriberTest extends TestCase
{
    /** @var Security&MockObject */
    private Security $security;
    private SignoffSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->subscriber = new SignoffSubscriber($this->security);
    }

    public function testOnSubmitReturnCallsSignoffWithUser(): void
    {
        $user = $this->createMock(User::class);
        $this->security->method('getUser')->willReturn($user);

        $fundReturn = $this->createMock(FundReturn::class);
        $fundReturn
            ->expects($this->once())
            ->method('signoff')
            ->with($user);

        $event = $this->createWorkflowEvent($fundReturn);

        $this->subscriber->onSubmitReturn($event);
    }

    public function testOnSubmitReturnThrowsExceptionForInvalidSubject(): void
    {
        $user = $this->createMock(User::class);
        $this->security->method('getUser')->willReturn($user);

        $invalidSubject = new \stdClass();
        $event = $this->createWorkflowEvent($invalidSubject);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid subject for submit_return transition');

        $this->subscriber->onSubmitReturn($event);
    }

    public function testWorksWithDifferentFundReturnTypes(): void
    {
        $user = $this->createMock(User::class);
        $this->security->method('getUser')->willReturn($user);

        // Create a concrete FundReturn subclass mock
        $concreteFundReturn = $this->getMockBuilder(FundReturn::class)
            ->onlyMethods(['signoff'])
            ->getMockForAbstractClass();

        $concreteFundReturn
            ->expects($this->once())
            ->method('signoff')
            ->with($user);

        $event = $this->createWorkflowEvent($concreteFundReturn);

        $this->subscriber->onSubmitReturn($event);
    }

    private function createWorkflowEvent($subject): Event
    {
        $workflow = $this->createMock(WorkflowInterface::class);
        $transition = new Transition('submit_return', 'open', 'submitted');
        $marking = new Marking(['open' => 1]);

        return new Event($subject, $marking, $transition, $workflow);
    }
}
