<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Subscriber;

use Frosh\TemplateMail\Services\MailFinderService;
use Frosh\TemplateMail\Services\MailFinderServiceInterface;
use Frosh\TemplateMail\Services\TemplateMailContext;
use Shopware\Core\Content\Flow\Events\FlowSendMailActionEvent;
use Shopware\Core\Content\MailTemplate\Aggregate\MailTemplateType\MailTemplateTypeCollection;
use Shopware\Core\Content\MailTemplate\Aggregate\MailTemplateType\MailTemplateTypeEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Adapter\Translation\AbstractTranslator;
use Shopware\Core\Framework\Adapter\Translation\Translator;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\Language\LanguageCollection;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class FlowSubscriber implements EventSubscriberInterface
{
    /**
     * @param EntityRepository<MailTemplateTypeCollection> $mailTemplateTypeRepository
     * @param EntityRepository<LanguageCollection> $languageRepository
     * @param EntityRepository<SalesChannelCollection> $salesChannelRepository
     */
    public function __construct(
        private readonly EntityRepository $mailTemplateTypeRepository,
        private readonly MailFinderServiceInterface $mailFinderService,
        #[Autowire(service: Translator::class)]
        private readonly AbstractTranslator $translator,
        private readonly EntityRepository $languageRepository,
        private readonly SystemConfigService $systemConfigService,
        private readonly EntityRepository $salesChannelRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FlowSendMailActionEvent::class => 'onFlowSendMailActionEvent',
        ];
    }

    public function onFlowSendMailActionEvent(FlowSendMailActionEvent $event): void
    {
        $mailTemplateTypeId = $event->getMailTemplate()->getMailTemplateTypeId();
        if ($mailTemplateTypeId === null) {
            return;
        }

        $this->sendMail($event->getDataBag(), $mailTemplateTypeId, $event->getContext());
    }

    private function sendMail(DataBag $dataBag, string $mailTemplateTypeId, Context $context): void
    {
        /** @var MailTemplateTypeEntity $mailTemplateType */
        $mailTemplateType = $this->mailTemplateTypeRepository->search(new Criteria([$mailTemplateTypeId]), $context)->first();

        $technicalName = $mailTemplateType->getTechnicalName();
        $templateId = $dataBag->get('templateId', null);
        \assert($templateId === null || \is_string($templateId));

        $event = $this->createTemplateMailContext($dataBag, $context);

        $this->fixTranslator($event);

        $html = $this->mailFinderService->findTemplateByTechnicalName(MailFinderService::TYPE_HTML, $technicalName, $event, false, $templateId);
        $plain = $this->mailFinderService->findTemplateByTechnicalName(MailFinderService::TYPE_PLAIN, $technicalName, $event, false, $templateId);
        $subject = $this->mailFinderService->findTemplateByTechnicalName(MailFinderService::TYPE_SUBJECT, $technicalName, $event, false, $templateId);

        if ($html) {
            $dataBag->set('contentHtml', $html);
        }

        if ($plain) {
            $dataBag->set('contentPlain', $plain);
        }

        $salesChannelId = $dataBag->get('salesChannelId') ?: null;
        if ($subject && \is_string($salesChannelId)) {
            $debugMode = $this->systemConfigService->getBool('FroshPlatformTemplateMail.config.debugMode', $salesChannelId);
            if ($debugMode) {
                $subject = \sprintf(
                    'DEBUG: %s (%s - %s - %s)',
                    $subject,
                    $this->getSalesChannelName($salesChannelId, $context),
                    $this->getLocaleCode($context->getLanguageId(), $context),
                    $technicalName,
                );
            }
            $dataBag->set('subject', $subject);
        }
    }

    private function createTemplateMailContext(ParameterBag $dataBag, Context $context): TemplateMailContext
    {
        $salesChannelId = $dataBag->get('salesChannelId');
        $languageId = $dataBag->get('languageId');

        return new TemplateMailContext(
            \is_string($salesChannelId) ? $salesChannelId : Defaults::SALES_CHANNEL_TYPE_STOREFRONT,
            $context,
            \is_string($languageId) ? $languageId : null,
        );
    }

    private function fixTranslator(TemplateMailContext $businessEvent): void
    {
        $languageId = $businessEvent->getLanguageId() ?? $businessEvent->getContext()->getLanguageId();
        $localCode = $this->getLocaleCode(
            $languageId,
            $businessEvent->getContext(),
        );

        if ($localCode === null) {
            return;
        }

        $this->translator->injectSettings(
            $businessEvent->getSalesChannelId(),
            $languageId,
            $localCode,
            $businessEvent->getContext(),
        );
    }

    private function getLocaleCode(string $languageId, Context $context): ?string
    {
        $criteria = new Criteria([$languageId]);
        $criteria->addAssociation('locale');

        /** @var LanguageEntity $language */
        $language = $this->languageRepository->search($criteria, $context)->first();

        return $language->getLocale()?->getCode();
    }

    private function getSalesChannelName(string $salesChannelId, Context $context): string
    {
        $criteria = new Criteria([$salesChannelId]);

        /** @var SalesChannelEntity $salesChannel */
        $salesChannel = $this->salesChannelRepository->search($criteria, $context)->first();

        /** @var string */
        $name = $salesChannel->getTranslation('name');

        return $name;
    }
}
