<?php declare(strict_types=1);

namespace Frosh\TemplateMail\Event;

use Shopware\Core\Content\Flow\Events\FlowSendMailActionEvent;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\EventData\EventDataCollection;
use Shopware\Core\Framework\Event\EventData\MailRecipientStruct;

/**
 * @codeCoverageIgnore
 */
class TemplateMailBusinessEvent extends FlowSendMailActionEvent
{
    public function __construct(
        private readonly string $salesChannelId,
        private readonly Context $context
    ) {
    }

    public static function getAvailableData(): EventDataCollection
    {
        return new EventDataCollection();
    }

    public function getName(): string
    {
        return 'TemplateMailBusinessEvent';
    }

    public function getMailStruct(): MailRecipientStruct
    {
        return new MailRecipientStruct([]);
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
