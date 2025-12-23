<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Services;

use Frosh\TemplateMail\DTO\TemplateData;

interface MailFinderServiceInterface
{
    public function getTemplateDataByTechnicalName(
        string $technicalName,
        TemplateMailContext $businessEvent,
        ?string $mailTemplateId = null,
    ): TemplateData;
}
