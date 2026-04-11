<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Services\MjmlRenderer;

use Frosh\TemplateMail\Exception\MjmlCompileError;
use Psr\Log\LoggerInterface;
use Spatie\Mjml\Mjml;
use Spatie\Mjml\MjmlError;

class LocalMjmlRenderer implements MjmlRendererInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function render(string $mjml): string
    {
        try {
            $result = Mjml::new()->convert($mjml);
        } catch (\Exception $e) {
            $this->logger->critical('Local MJML rendering failed', ['error' => $e->getMessage()]);

            // Return empty string to load shopware default templates.
            return '';
        }

        if ($result->hasErrors()) {
            foreach ($result->errors() as $error) {
                $this->logger->critical('Error during local MJML compilation', ['error' => $error->formattedMessage()]);
            }

            throw new MjmlCompileError(implode("\n", array_map(
                static fn (MjmlError $error): string => $error->formattedMessage(),
                $result->errors()
            )));
        }

        return $result->html();
    }
}
