<?php

namespace Frosh\TemplateMail\Subscriber;

use Frosh\TemplateMail\Event\TemplateMailBusinessEvent;
use Frosh\TemplateMail\Services\MailFinderService;
use Frosh\TemplateMail\Services\MailFinderServiceInterface;
use Shopware\Core\Content\Flow\Events\FlowSendMailActionEvent;
use Shopware\Core\Content\MailTemplate\Aggregate\MailTemplateType\MailTemplateTypeEntity;
use Shopware\Core\Content\MailTemplate\Event\MailSendSubscriberBridgeEvent;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Adapter\Translation\AbstractTranslator;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\Language\LanguageEntity;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class FlowSubscriber implements EventSubscriberInterface
{
    private EntityRepository $mailTemplateTypeRepository;
    private MailFinderServiceInterface $mailFinderService;
    private EntityRepository $languageRepository;
    private AbstractTranslator $translator;

    public function __construct(
        EntityRepository $mailTemplateTypeRepository,
        MailFinderServiceInterface $mailFinderService,
        EntityRepository $languageRepository,
        AbstractTranslator $translator
    )
    {
        $this->translator = $translator;
        $this->languageRepository = $languageRepository;
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

    public function sendMail(DataBag $dataBag, string $mailTemplateTypeId, Context $context): void
    {
        /** @var MailTemplateTypeEntity $mailTemplateType */
        $mailTemplateType = $this->mailTemplateTypeRepository->search(new Criteria([$mailTemplateTypeId]), $context) ->first();

        $technicalName = $mailTemplateType->getTechnicalName();
        $event = $this->createBusinessEventFromBag($dataBag, $context);

        $this->fixTranslator($event);

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

    private function createBusinessEventFromBag(ParameterBag $dataBag, Context $context): TemplateMailBusinessEvent
    {
        return new TemplateMailBusinessEvent($dataBag->get('salesChannelId') ?? Defaults::SALES_CHANNEL, $context);
    }

    private function fixTranslator(TemplateMailBusinessEvent $businessEvent): void
    {
        $criteria = new Criteria([$businessEvent->getContext()->getLanguageId()]);
        $criteria->addAssociation('locale');

        /** @var LanguageEntity $language */
        $language = $this->languageRepository->search($criteria, $businessEvent->getContext())->first();

        $locale = $language->getLocale();
        if ($locale === null) {
            return;
        }

        $this->translator->injectSettings(
            $businessEvent->getSalesChannelId(),
            $businessEvent->getContext()->getLanguageId(),
            $locale->getCode(),
            $businessEvent->getContext()
        );
    }
}
