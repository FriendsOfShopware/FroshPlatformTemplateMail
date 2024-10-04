<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Services;

interface MailFinderServiceInterface
{
    public function findTemplateByTechnicalName(
        string $type,
        string $technicalName,
        TemplateMailContext $businessEvent,
        bool $returnFolder = false,
        ?string $mailTemplateId = null,
    ): ?string;
}
