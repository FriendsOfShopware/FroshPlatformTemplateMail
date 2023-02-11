<?php declare(strict_types=1);

namespace Frosh\TemplateMail\Services\MailLoader;

class TwigLoader implements LoaderInterface
{
    public function load(string $path): ?string
    {
        return file_get_contents($path) ?: null;
    }

    public function supportedExtensions(): array
    {
        return ['twig'];
    }
}
