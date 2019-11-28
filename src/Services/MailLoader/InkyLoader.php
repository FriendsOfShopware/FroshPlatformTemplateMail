<?php

namespace Frosh\TemplateMail\Services\MailLoader;

use Psr\Log\LoggerInterface;

class InkyLoader implements LoaderInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function load(string $path): ?string
    {
//        try {
            return (\Pinky\transformFile($path))->saveHTML();
//        } catch (\Throwable $e) {
//            $this->logger->critical(sprintf('Cannot process file %s with Pinky', $path), ['exception' => $e]);
//            return null;
//        }
    }

    public function supportedExtensions(): array
    {
        return ['inky.html'];
    }
}
