<?php

namespace Frosh\TemplateMail\Event;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\BusinessEvent;
use Shopware\Core\Framework\Event\EventData\EventDataCollection;
use Shopware\Core\Framework\Event\EventData\MailRecipientStruct;
use Shopware\Core\Framework\Event\MailActionInterface;

/**
 * @codeCoverageIgnore
 */
class TemplateMailBusinessEvent extends BusinessEvent implements MailActionInterface
{
    /**
     * @var string
     */
    private $salesChannelId;

    /**
     * @var Context
     */
    private $context;

    public function __construct(string $salesChannelId, Context $context)
    {
        $this->salesChannelId = $salesChannelId;
        $this->context = $context;
        parent::__construct('action.send.mail', $this);
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

    public function getSalesChannelId(): ?string
    {
        return $this->salesChannelId;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
