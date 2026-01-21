<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Subscriber;

use Frosh\TemplateMail\Services\MailFinderServiceInterface;
use Frosh\TemplateMail\Services\TemplateMailContext;
use Shopware\Core\Content\MailTemplate\Aggregate\MailTemplateType\MailTemplateTypeCollection;
use Shopware\Core\Content\MailTemplate\Aggregate\MailTemplateType\MailTemplateTypeEntity;
use Shopware\Core\Content\MailTemplate\MailTemplateEntity;
use Shopware\Core\Content\MailTemplate\MailTemplateEvents;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Api\Context\SalesChannelApiSource;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MailTemplateSubscriber implements EventSubscriberInterface
{
    /**
     * @param EntityRepository<MailTemplateTypeCollection> $mailTemplateTypeRepository
     */
    public function __construct(
        private readonly EntityRepository $mailTemplateTypeRepository,
        private readonly MailFinderServiceInterface $mailFinderService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MailTemplateEvents::MAIL_TEMPLATE_LOADED_EVENT => 'onMailTemplatesLoaded',
        ];
    }

    /**
     * @param EntityLoadedEvent<MailTemplateEntity> $event
     */
    public function onMailTemplatesLoaded(EntityLoadedEvent $event): void
    {
        $source = $event->getContext()->getSource();
        $salesChannelId = Defaults::SALES_CHANNEL_TYPE_STOREFRONT;

        if ($source instanceof SalesChannelApiSource) {
            $salesChannelId = $source->getSalesChannelId();
        }

        $businessEvent = new TemplateMailContext($salesChannelId, $event->getContext());

        /** @var MailTemplateTypeCollection $mailTemplateTypes */
        $mailTemplateTypes = $this->mailTemplateTypeRepository->search(new Criteria(), $event->getContext())->getEntities();

        /** @var MailTemplateEntity $mailTemplateEntity */
        foreach ($event->getEntities() as $mailTemplateEntity) {
            $mailTemplateTypeId = $mailTemplateEntity->getMailTemplateTypeId();
            if (!$mailTemplateTypeId) {
                continue;
            }

            $mailTemplateType = $mailTemplateTypes->get($mailTemplateTypeId);
            if (!$mailTemplateType instanceof MailTemplateTypeEntity) {
                continue;
            }

            $technicalName = $mailTemplateType->getTechnicalName();

            $templateData = $this->mailFinderService->getTemplateDataByTechnicalName($technicalName, $businessEvent, $mailTemplateEntity->getId());
            if ($templateData->subject !== null) {
                $mailTemplateEntity->setSubject($templateData->subject->content);
                $mailTemplateEntity->addTranslated('subject', $templateData->subject->content);
            }

            if ($templateData->html !== null) {
                $mailTemplateEntity->setContentHtml($templateData->html->content);
                $mailTemplateEntity->addTranslated('contentHtml', $templateData->html->content);
            }

            if ($templateData->plain !== null) {
                $mailTemplateEntity->setContentPlain($templateData->plain->content);
                $mailTemplateEntity->addTranslated('contentPlain', $templateData->plain->content);
            }

            $mailTemplateEntity->addExtension(
                'froshTemplateMail',
                new ArrayStruct([
                    'subject' => $templateData->subject?->filePath,
                    'html' => $templateData->html?->filePath,
                    'plain' => $templateData->plain?->filePath,
                    'technicalName' => $technicalName,
                ]),
            );
        }
    }
}
