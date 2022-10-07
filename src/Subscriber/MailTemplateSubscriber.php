<?php

namespace Frosh\TemplateMail\Subscriber;

use Frosh\TemplateMail\Event\TemplateMailBusinessEvent;
use Frosh\TemplateMail\Services\MailFinderService;
use Frosh\TemplateMail\Services\MailFinderServiceInterface;
use Shopware\Core\Content\MailTemplate\Aggregate\MailTemplateType\MailTemplateTypeCollection;
use Shopware\Core\Content\MailTemplate\MailTemplateEntity;
use Shopware\Core\Content\MailTemplate\MailTemplateEvents;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Api\Context\SalesChannelApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MailTemplateSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $mailTemplateTypeRepository;

    /**
     * @var MailFinderServiceInterface
     */
    private $mailFinderService;

    public function __construct(
        EntityRepositoryInterface $mailTemplateTypeRepository,
        MailFinderServiceInterface $mailFinderService
    ) {
        $this->mailTemplateTypeRepository = $mailTemplateTypeRepository;
        $this->mailFinderService = $mailFinderService;
    }


    public static function getSubscribedEvents(): array
    {
        return [
            MailTemplateEvents::MAIL_TEMPLATE_LOADED_EVENT => 'onMailTemplatesLoaded',
        ];
    }

    public function onMailTemplatesLoaded(EntityLoadedEvent $event): void
    {
        $source = $event->getContext()->getSource();
        $salesChannelId = Defaults::SALES_CHANNEL;

        if ($source instanceof SalesChannelApiSource) {
            $salesChannelId = $source->getSalesChannelId();
        }

        $businessEvent = new TemplateMailBusinessEvent($salesChannelId, $event->getContext());
        $context = Context::createDefaultContext();

        /** @var MailTemplateTypeCollection $mailTemplateTypes */
        $mailTemplateTypes = $this->mailTemplateTypeRepository->search(new Criteria(), $context)->getEntities();

        /** @var MailTemplateEntity $mailTemplateEntity */
        foreach ($event->getEntities() as $mailTemplateEntity) {

            $mailTemplateType = $mailTemplateTypes->get($mailTemplateEntity->getMailTemplateTypeId());
            if($mailTemplateType === null) {
                continue;
            }

            $technicalName = $mailTemplateType->getTechnicalName();
            $html = $this->mailFinderService->findTemplateByTechnicalName(MailFinderService::TYPE_HTML, $technicalName, $businessEvent, true);
            $plain = $this->mailFinderService->findTemplateByTechnicalName(MailFinderService::TYPE_PLAIN, $technicalName, $businessEvent, true);
            $subject = $this->mailFinderService->findTemplateByTechnicalName(MailFinderService::TYPE_SUBJECT, $technicalName, $businessEvent, true);

            $mailTemplateEntity->addExtension(
                'froshTemplateMail',
                new ArrayStruct([
                    'html' => $html,
                    'plain' => $plain,
                    'subject' => $subject,
                    'technicalName' => $technicalName,
                ]));
        }
    }
}
