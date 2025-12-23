<?php declare(strict_types=1);

namespace Frosh\TemplateMail\DTO;

class TypeData
{
    public function __construct(
        public string $filePath,
        public string $content
    ) {
    }
}
