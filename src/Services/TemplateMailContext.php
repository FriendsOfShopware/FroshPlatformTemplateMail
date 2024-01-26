<?php declare(strict_types=1);

namespace Frosh\TemplateMail\Services;

use Shopware\Core\Content\Flow\Events\FlowSendMailActionEvent;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\EventData\EventDataCollection;
use Shopware\Core\Framework\Event\EventData\MailRecipientStruct;

/**
 * @codeCoverageIgnore
 */
readonly class TemplateMailContext
{
    public function __construct(
        private string $salesChannelId,
        private Context $context
    ) {
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
