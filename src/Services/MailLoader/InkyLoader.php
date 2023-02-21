<?php declare(strict_types=1);

namespace Frosh\TemplateMail\Services\MailLoader;

use function Pinky\transformFile;

class InkyLoader implements LoaderInterface
{
    public function load(string $path): ?string
    {
        return (transformFile($path))->saveHTML() ?: null;
    }

    public function supportedExtensions(): array
    {
        return ['inky.html'];
    }
}
