<?php declare(strict_types=1);

namespace Frosh\TemplateMail\Subscriber;

use Frosh\TemplateMail\Event\MailDataBagFilter;
use Frosh\TemplateMail\Event\TemplateMailBusinessEvent;
use Frosh\TemplateMail\Services\MailFinderService;
use Frosh\TemplateMail\Services\MailFinderServiceInterface;
use Shopware\Core\Content\MailTemplate\Aggregate\MailTemplateType\MailTemplateTypeEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Event\BusinessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class MailDataBagFilterSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $mailTemplateTypeRepository;

    /**
     * @var MailFinderServiceInterface
     */
    private $mailFinderService;

    public function __construct(EntityRepositoryInterface $mailTemplateTypeRepository, MailFinderServiceInterface $mailFinderService)
    {
        $this->mailTemplateTypeRepository = $mailTemplateTypeRepository;
        $this->mailFinderService = $mailFinderService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MailDataBagFilter::class => 'filterMailData'
        ];
    }

    public function filterMailData(MailDataBagFilter $bagFilter): void
    {
        /** @var MailTemplateTypeEntity $mailTemplateType */
        $mailTemplateType = $this->mailTemplateTypeRepository->search(new Criteria([$bagFilter->getMailTemplateEntity()->getMailTemplateTypeId()]), Context::createDefaultContext()) ->first();

        $technicalName = $mailTemplateType->getTechnicalName();
        $event = $this->createBusinessEventFromBag($bagFilter->getDataBag(), $bagFilter->getContext());

        $html = $this->mailFinderService->findTemplateByTechnicalName(MailFinderService::TYPE_HTML, $technicalName, $event);
        $plain = $this->mailFinderService->findTemplateByTechnicalName(MailFinderService::TYPE_PLAIN, $technicalName, $event);
        $subject = $this->mailFinderService->findTemplateByTechnicalName(MailFinderService::TYPE_SUBJECT, $technicalName, $event);

        if ($html) {
            $bagFilter->getDataBag()->set('contentHtml', $html);
        }

        if ($plain) {
            $bagFilter->getDataBag()->set('contentPlain', $plain);
        }

        if ($subject) {
            $bagFilter->getDataBag()->set('subject', $subject);
        }
    }

    private function createBusinessEventFromBag(ParameterBag $dataBag, Context $context): BusinessEvent
    {
        return new TemplateMailBusinessEvent($dataBag->get('salesChannelId'), $context);
    }
}
