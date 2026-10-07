<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Tests\Subscriber;

use Frosh\TemplateMail\DTO\TemplateData;
use Frosh\TemplateMail\Services\MailFinderServiceInterface;
use Frosh\TemplateMail\Services\TemplateMailContext;
use Frosh\TemplateMail\Subscriber\MailBeforeValidateSubscriber;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\MailTemplate\Aggregate\MailTemplateType\MailTemplateTypeCollection;
use Shopware\Core\Content\MailTemplate\Service\Event\MailBeforeValidateEvent;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\PartialEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;

class MailBeforeValidateSubscriberTest extends TestCase
{
    public function testUsesMailTemplateTypeFromEntitiesCollection(): void
    {
        $mailTemplateType = $this->createMock(PartialEntity::class);
        $mailTemplateType->expects(static::once())
            ->method('get')
            ->with('technicalName')
            ->willReturn('technical-name');

        $entities = $this->createMock(MailTemplateTypeCollection::class);
        $entities->expects(static::once())
            ->method('first')
            ->willReturn($mailTemplateType);

        $searchResult = $this->createMock(EntitySearchResult::class);
        $searchResult->expects(static::once())
            ->method('getEntities')
            ->willReturn($entities);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(static::once())
            ->method('search')
            ->willReturn($searchResult);

        $mailFinder = $this->createMock(MailFinderServiceInterface::class);
        $mailFinder->expects(static::once())
            ->method('getTemplateDataByTechnicalName')
            ->with('technical-name', static::isInstanceOf(TemplateMailContext::class), null)
            ->willReturn(new TemplateData());

        $subscriber = new MailBeforeValidateSubscriber($repository, $mailFinder);
        $event = new MailBeforeValidateEvent(
            ['mailTemplateTypeId' => 'mail-template-type-id'],
            Context::createDefaultContext(),
        );
        $subscriber->onMailBeforeValidate($event);

        static::assertSame(['mailTemplateTypeId' => 'mail-template-type-id'], $event->getData());
    }
}
