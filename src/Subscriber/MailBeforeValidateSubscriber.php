<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Subscriber;

use Frosh\TemplateMail\Services\MailFinderServiceInterface;
use Frosh\TemplateMail\Services\TemplateMailContext;
use Shopware\Core\Content\MailTemplate\Aggregate\MailTemplateType\MailTemplateTypeCollection;
use Shopware\Core\Content\MailTemplate\Service\Event\MailBeforeValidateEvent;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\PartialEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MailBeforeValidateSubscriber implements EventSubscriberInterface
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
            MailBeforeValidateEvent::class => 'onMailBeforeValidate',
        ];
    }

    public function onMailBeforeValidate(MailBeforeValidateEvent $event): void
    {
        $data = $event->getData();

        $mailTemplateTypeId = $data['mailTemplateTypeId'] ?? null;
        if (!\is_string($mailTemplateTypeId)) {
            return;
        }

        $technicalName = $this->getTechnicalName($mailTemplateTypeId, $event->getContext());

        $salesChannelId = $data['salesChannelId'] ?? Defaults::SALES_CHANNEL_TYPE_STOREFRONT;
        $mailTemplateId = $data['mailTemplateId'] ?? null;

        $templateMailContext = new TemplateMailContext(
            \is_string($salesChannelId) ? $salesChannelId : Defaults::SALES_CHANNEL_TYPE_STOREFRONT,
            $event->getContext(),
        );

        $templateData = $this->mailFinderService->getTemplateDataByTechnicalName(
            $technicalName,
            $templateMailContext,
            \is_string($mailTemplateId) ? $mailTemplateId : null,
        );

        if ($templateData->html !== null) {
            $data['contentHtml'] = $templateData->html->content;
        }

        if ($templateData->plain !== null) {
            $data['contentPlain'] = $templateData->plain->content;
        }

        if ($templateData->subject !== null) {
            $data['subject'] = $templateData->subject->content;
        }

        $event->setData($data);
    }

    private function getTechnicalName(string $mailTemplateTypeId, Context $context): string
    {
        $criteria = new Criteria([$mailTemplateTypeId]);
        $criteria->addFields(['technicalName']);

        /** @var PartialEntity|null $mailTemplateType */
        $mailTemplateType = $this->mailTemplateTypeRepository->search($criteria, $context)->first();

        $technicalName = $mailTemplateType?->get('technicalName');

        if (!\is_string($technicalName)) {
            throw new \RuntimeException('technicalName could not be determined');
        }

        return $technicalName;
    }
}
