<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Services;

use Shopware\Core\Framework\Context;

/**
 * @codeCoverageIgnore
 */
class TemplateMailContext
{
    public function __construct(
        private readonly string $salesChannelId,
        private readonly Context $context,
        private readonly ?string $languageId = null,
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

    public function getLanguageId(): ?string
    {
        return $this->languageId;
    }
}
