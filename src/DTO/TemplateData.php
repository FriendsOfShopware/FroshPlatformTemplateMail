<?php declare(strict_types=1);

namespace Frosh\TemplateMail\DTO;

class TemplateData
{
    public function __construct(
        public ?TypeData $subject = null,
        public ?TypeData $html = null,
        public ?TypeData $plain = null,
    ) {
    }

    public function isAllSet(): bool
    {
        return $this->subject !== null
            && $this->html !== null
            && $this->plain !== null;
    }
}
