<?php declare(strict_types=1);

namespace Frosh\TemplateMail\DTO;

enum TemplateType
{
    case SUBJECT;
    case HTML;
    case PLAIN;

    public function filePart(): string
    {
        return match ($this) {
            self::SUBJECT => 'subject.',
            self::HTML => 'html.',
            self::PLAIN => 'plain.',
        };
    }
}
