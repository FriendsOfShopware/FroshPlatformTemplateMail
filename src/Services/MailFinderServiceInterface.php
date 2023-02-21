<?php declare(strict_types=1);

namespace Frosh\TemplateMail\Services;

use Frosh\TemplateMail\Services\TemplateMailContext;

interface MailFinderServiceInterface
{
    public function findTemplateByTechnicalName(
        string              $type,
        string              $technicalName,
        TemplateMailContext $businessEvent,
        bool                $returnFolder = false
    ): ?string;
}
