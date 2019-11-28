<?php

namespace Frosh\TemplateMail\Subscriber;

use Frosh\TemplateMail\Services\Configuration;
use Frosh\TemplateMail\Services\MailFinderService;
use Frosh\TemplateMail\Services\MailFinderServiceInterface;
use kranemora\Html2Text\Html2Text;
use Shopware\Core\Content\MailTemplate\Exception\MailEventConfigurationException;
use Shopware\Core\Content\MailTemplate\MailTemplateEntity;
use Shopware\Core\Content\MailTemplate\Service\MailService;
use Shopware\Core\Content\MailTemplate\Subscriber\MailSendSubscriber as ShopwareMailSenderSubscriber;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Event\BusinessEvent;
use Shopware\Core\Framework\Event\EventData\EventDataType;
use Shopware\Core\Framework\Event\MailActionInterface;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MailSendSubscriber implements EventSubscriberInterface
{
    /**
     * @var MailService
     */
    private $mailService;

    /**
     * @var EntityRepositoryInterface
     */
    private $mailTemplateRepository;

    /**
     * @var MailFinderServiceInterface
     */
    private $mailFinderService;

    public function __construct(
        MailService $mailService,
        EntityRepositoryInterface $mailTemplateRepository,
        MailFinderServiceInterface $mailFinderService
    ) {
        $this->mailService = $mailService;
        $this->mailTemplateRepository = $mailTemplateRepository;
        $this->mailFinderService = $mailFinderService;
    }


    public static function getSubscribedEvents(): array
    {
        return [
            ShopwareMailSenderSubscriber::ACTION_NAME => 'sendMail'
        ];
    }

    public function sendMail(BusinessEvent $event): void
    {
        $mailEvent = $event->getEvent();

        if (!$mailEvent instanceof MailActionInterface) {
            throw new MailEventConfigurationException('Not a instance of MailActionInterface', get_class($mailEvent));
        }

        if (!\array_key_exists('mail_template_type_id', $event->getConfig())) {
            throw new MailEventConfigurationException('Configuration mail_template_type_id missing.', get_class($mailEvent));
        }

        $mailTemplate = $this->loadMailTemplate($event);

        if ($mailTemplate === null) {
            return;
        }

        $technicalName = $mailTemplate->getMailTemplateType()->getTechnicalName();

        $html = $this->mailFinderService->findTemplateByTechnicalName(MailFinderService::TYPE_HTML, $technicalName, $event);
        $plain = $this->mailFinderService->findTemplateByTechnicalName(MailFinderService::TYPE_PLAIN, $technicalName, $event);
        $subject = $this->mailFinderService->findTemplateByTechnicalName(MailFinderService::TYPE_SUBJECT, $technicalName, $event);

        $data = new DataBag();
        $data->set('recipients', $mailEvent->getMailStruct()->getRecipients());
        $data->set('senderName', $mailTemplate->getTranslation('senderName'));
        $data->set('salesChannelId', $mailEvent->getSalesChannelId());

        $data->set('contentHtml', $html ? $html : $mailTemplate->getTranslation('contentHtml'));
        $data->set('contentPlain', $plain ? $plain : $mailTemplate->getTranslation('contentPlain'));
        $data->set('subject', $subject ? $subject : $mailTemplate->getTranslation('subject'));
        $data->set('mediaIds', []);

        $this->mailService->send(
            $data->all(),
            $event->getContext(),
            $this->getTemplateData($mailEvent)
        );
    }

    /**
     * @throws MailEventConfigurationException
     */
    private function getTemplateData(MailActionInterface $event): array
    {
        $data = [];
        /* @var EventDataType $item */
        foreach (array_keys($event::getAvailableData()->toArray()) as $key) {
            $getter = 'get' . ucfirst($key);
            if (method_exists($event, $getter)) {
                $data[$key] = $event->$getter();
            } else {
                throw new MailEventConfigurationException('Data for ' . $key . ' not available.', get_class($event));
            }
        }

        return $data;
    }

    protected function loadMailTemplate(BusinessEvent $event)
    {
        $mailTemplateTypeId = $event->getConfig()['mail_template_type_id'];
        $mailEvent = $event->getEvent();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('mailTemplateTypeId', $mailTemplateTypeId));
        $criteria->addAssociation('mailTemplateType');
        $criteria->setLimit(1);

        if ($mailEvent->getSalesChannelId()) {
            $criteria->addFilter(new EqualsFilter('mail_template.salesChannels.salesChannel.id', $mailEvent->getSalesChannelId()));

            /** @var MailTemplateEntity|null $mailTemplate */
            $mailTemplate = $this->mailTemplateRepository->search($criteria, $event->getContext())->first();

            // Fallback if no template for the saleschannel is found
            if ($mailTemplate === null) {
                $criteria = new Criteria();
                $criteria->addFilter(new EqualsFilter('mailTemplateTypeId', $mailTemplateTypeId));
                $criteria->addAssociation('mailTemplateType');
                $criteria->setLimit(1);

                /** @var MailTemplateEntity|null $mailTemplate */
                $mailTemplate = $this->mailTemplateRepository->search($criteria, $event->getContext())->first();
            }
        } else {
            /** @var MailTemplateEntity|null $mailTemplate */
            $mailTemplate = $this->mailTemplateRepository->search($criteria, $event->getContext())->first();
        }
        return $mailTemplate;
    }
}
