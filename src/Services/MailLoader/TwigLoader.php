<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Services\MailLoader;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias]
class TwigLoader implements LoaderInterface
{
    public function load(string $path): ?string
    {
        return file_get_contents($path) ?: null;
    }

    /**
     * @return string[]
     */
    public function supportedExtensions(): array
    {
        return ['twig'];
    }
}
