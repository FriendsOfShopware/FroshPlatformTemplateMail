<?php declare(strict_types=1);

namespace Frosh\TemplateMail\Services;

use Frosh\TemplateMail\Event\TemplateMailBusinessEvent;

interface MailFinderServiceInterface
{
    public function findTemplateByTechnicalName(
        string $type,
        string $technicalName,
        TemplateMailBusinessEvent $businessEvent,
        bool $returnFolder = false
    ): ?string;
}
