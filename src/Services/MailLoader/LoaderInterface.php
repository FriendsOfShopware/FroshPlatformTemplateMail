<?php declare(strict_types=1);

namespace Frosh\TemplateMail\Services\MailLoader;

interface LoaderInterface
{
    public function load(string $path): ?string;

    public function supportedExtensions(): array;
}
