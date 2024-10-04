<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Subscriber;

use Frosh\TemplateMail\Services\TemplateMailContext;
use Frosh\TemplateMail\Services\MailFinderService;
use Frosh\TemplateMail\Services\MailFinderServiceInterface;
use Shopware\Core\Content\MailTemplate\Aggregate\MailTemplateType\MailTemplateTypeCollection;
use Shopware\Core\Content\MailTemplate\Aggregate\MailTemplateType\MailTemplateTypeEntity;
use Shopware\Core\Content\MailTemplate\MailTemplateEntity;
use Shopware\Core\Content\MailTemplate\MailTemplateEvents;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Api\Context\SalesChannelApiSource;
use Shopware\Core\Framework\Context;
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
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            MailTemplateEvents::MAIL_TEMPLATE_LOADED_EVENT => 'onMailTemplatesLoaded',
        ];
    }

    public function onMailTemplatesLoaded(EntityLoadedEvent $event): void
    {
        $source = $event->getContext()->getSource();
        $salesChannelId = Defaults::SALES_CHANNEL_TYPE_STOREFRONT;

        if ($source instanceof SalesChannelApiSource) {
            $salesChannelId = $source->getSalesChannelId();
        }

        $businessEvent = new TemplateMailContext($salesChannelId, $event->getContext());
        $context = Context::createDefaultContext();

        /** @var MailTemplateTypeCollection $mailTemplateTypes */
        $mailTemplateTypes = $this->mailTemplateTypeRepository->search(new Criteria(), $context)->getEntities();

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
            $html = $this->mailFinderService->findTemplateByTechnicalName(MailFinderService::TYPE_HTML, $technicalName, $businessEvent, true, $mailTemplateEntity->getId());
            $plain = $this->mailFinderService->findTemplateByTechnicalName(MailFinderService::TYPE_PLAIN, $technicalName, $businessEvent, true, $mailTemplateEntity->getId());
            $subject = $this->mailFinderService->findTemplateByTechnicalName(MailFinderService::TYPE_SUBJECT, $technicalName, $businessEvent, true, $mailTemplateEntity->getId());

            $mailTemplateEntity->addExtension(
                'froshTemplateMail',
                new ArrayStruct([
                    'html' => $html,
                    'plain' => $plain,
                    'subject' => $subject,
                    'technicalName' => $technicalName,
                ]),
            );
        }
    }
}
