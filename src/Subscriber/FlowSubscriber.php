<?php

namespace Frosh\TemplateMail\Subscriber;

use Frosh\TemplateMail\Event\TemplateMailBusinessEvent;
use Frosh\TemplateMail\Services\MailFinderService;
use Frosh\TemplateMail\Services\MailFinderServiceInterface;
use Shopware\Core\Content\Flow\Events\FlowSendMailActionEvent;
use Shopware\Core\Content\MailTemplate\Aggregate\MailTemplateType\MailTemplateTypeEntity;
use Shopware\Core\Content\MailTemplate\Event\MailSendSubscriberBridgeEvent;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Event\BusinessEvent;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class FlowSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityRepository
     */
    private $mailTemplateTypeRepository;

    /**
     * @var MailFinderServiceInterface
     */
    private $mailFinderService;

    public function __construct(
        EntityRepository $mailTemplateTypeRepository,
        MailFinderServiceInterface $mailFinderService
    )
    {
        $this->mailTemplateTypeRepository = $mailTemplateTypeRepository;
        $this->mailFinderService = $mailFinderService;
    }

    public static function getSubscribedEvents()
    {
        if (class_exists(FlowSendMailActionEvent::class)) {
            return [
                FlowSendMailActionEvent::class => 'onFlowSendMailActionEvent'
            ];
        }

        if (class_exists(MailSendSubscriberBridgeEvent::class)) {
            return [
                MailSendSubscriberBridgeEvent::class => 'onMailSendSubscriberBridgeEvent'
            ];
        }

        return [];
    }

    public function onFlowSendMailActionEvent(FlowSendMailActionEvent $event): void
    {
        $this->sendMail($event->getDataBag(), $event->getMailTemplate()->getMailTemplateTypeId(), $event->getContext());
    }

    public function onMailSendSubscriberBridgeEvent(MailSendSubscriberBridgeEvent $event): void
    {
        $this->sendMail($event->getDataBag(), $event->getMailTemplate()->getMailTemplateTypeId(), $event->getContext());
    }

    public function sendMail(DataBag $dataBag, string $mailTemplateTypeId, Context $context)
    {
        /** @var MailTemplateTypeEntity $mailTemplateType */
        $mailTemplateType = $this->mailTemplateTypeRepository->search(new Criteria([$mailTemplateTypeId]), $context) ->first();

        $technicalName = $mailTemplateType->getTechnicalName();
        $event = $this->createBusinessEventFromBag($dataBag, $context);

        $html = $this->mailFinderService->findTemplateByTechnicalName(MailFinderService::TYPE_HTML, $technicalName, $event);
        $plain = $this->mailFinderService->findTemplateByTechnicalName(MailFinderService::TYPE_PLAIN, $technicalName, $event);
        $subject = $this->mailFinderService->findTemplateByTechnicalName(MailFinderService::TYPE_SUBJECT, $technicalName, $event);

        if ($html) {
            $dataBag->set('contentHtml', $html);
        }

        if ($plain) {
            $dataBag->set('contentPlain', $plain);
        }

        if ($subject) {
            $dataBag->set('subject', $subject);
        }
    }

    private function createBusinessEventFromBag(ParameterBag $dataBag, Context $context): BusinessEvent
    {
        return new TemplateMailBusinessEvent($dataBag->get('salesChannelId') ?? Defaults::SALES_CHANNEL, $context);
    }
}
