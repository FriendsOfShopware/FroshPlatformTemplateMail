<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Subscriber;

use Frosh\TemplateMail\Services\TemplateMailContext;
use Frosh\TemplateMail\Services\MailFinderService;
use Frosh\TemplateMail\Services\MailFinderServiceInterface;
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
     * @param EntityRepository<SalesChannelEntity> $salesChannelRepository
     */
    public function __construct(
        private readonly EntityRepository $mailTemplateTypeRepository,
        private readonly MailFinderServiceInterface $mailFinderService,
        #[Autowire(service: Translator::class)]
        private readonly AbstractTranslator $translator,
        private readonly EntityRepository $languageRepository,
        private readonly SystemConfigService $systemConfigService,
        private readonly EntityRepository $salesChannelRepository,
    ) {}

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
        $event = $this->createTemplateMailContext($dataBag, $context);

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
            $salesChannelId = $dataBag->get('salesChannelId');
            $debugMode = $this->systemConfigService->getBool('FroshPlatformTemplateMail.config.debugMode', $salesChannelId);
            if ($debugMode) {
                $subject = sprintf(
                    'DEBUG: %s (%s - %s - %s)',
                    $subject,
                    $this->getSalesChannelName($salesChannelId, $context),
                    $this->getLocaleCode($context->getLanguageId(), $context),
                    $technicalName
                );
            }
            $dataBag->set('subject', $subject);
        }
    }

    private function createTemplateMailContext(ParameterBag $dataBag, Context $context): TemplateMailContext
    {
        $salesChannelId = $dataBag->get('salesChannelId');

        return new TemplateMailContext(
            \is_string($salesChannelId) ? $salesChannelId : Defaults::SALES_CHANNEL_TYPE_STOREFRONT,
            $context,
        );
    }

    private function fixTranslator(TemplateMailContext $businessEvent): void
    {
        $localCode = $this->getLocaleCode(
            $businessEvent->getContext()->getLanguageId(),
            $businessEvent->getContext()
        );

        if ($localCode === null) {
            return;
        }

        $this->translator->injectSettings(
            $businessEvent->getSalesChannelId(),
            $businessEvent->getContext()->getLanguageId(),
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

    private function getSalesChannelName(string $salesChannelId, Context $context): ?string
    {
        $criteria = new Criteria([$salesChannelId]);

        /** @var SalesChannelEntity $salesChannel */
        $salesChannel = $this->salesChannelRepository->search($criteria, $context)->first();

        return $salesChannel->getName();
    }
}
