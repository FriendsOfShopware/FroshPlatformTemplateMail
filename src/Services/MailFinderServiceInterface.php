<?php

namespace Frosh\TemplateMail\Services;

use Shopware\Core\Framework\Event\BusinessEvent;

interface MailFinderServiceInterface
{
    public function findTemplateByTechnicalName(
        string $type,
        string $technicalName,
        BusinessEvent $businessEvent,
        bool $returnFolder
    ): ?string;
}
