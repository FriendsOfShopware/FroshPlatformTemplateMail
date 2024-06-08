<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Tests\Subscriber;

use Frosh\TemplateMail\Services\MailFinderServiceInterface;
use Frosh\TemplateMail\Subscriber\FlowSubscriber;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Flow\Dispatching\StorableFlow;
use Shopware\Core\Content\Flow\Events\FlowSendMailActionEvent;
use Shopware\Core\Content\MailTemplate\MailTemplateEntity;
use Shopware\Core\Framework\Adapter\Translation\AbstractTranslator;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class FlowSubscriberTest extends TestCase
{
    public function testSubscribedEvents(): void
    {
        static::assertSame([
            FlowSendMailActionEvent::class => 'onFlowSendMailActionEvent',
        ], FlowSubscriber::getSubscribedEvents());
    }

    public function testEmptyMailDoesNothing(): void
    {
        $mailTemplateTypeRepository = $this->createMock(EntityRepository::class);
        $mailTemplateTypeRepository->expects(static::never())->method('search');

        $subscriber = new FlowSubscriber(
            $mailTemplateTypeRepository,
            $this->createMock(MailFinderServiceInterface::class),
            $this->createMock(AbstractTranslator::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(SystemConfigService::class),
            $this->createMock(EntityRepository::class),
        );

        $subscriber->onFlowSendMailActionEvent(new FlowSendMailActionEvent(new DataBag([]), new MailTemplateEntity(), new StorableFlow('test', Context::createDefaultContext())));
    }
}
