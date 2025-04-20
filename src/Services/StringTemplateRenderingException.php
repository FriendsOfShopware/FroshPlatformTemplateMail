<?php

namespace Frosh\TemplateMail\Services;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

// @phpstan-ignore-next-line
if (class_exists(\Shopware\Core\Framework\Adapter\Twig\Exception\StringTemplateRenderingException::class)) {
    // @phpstan-ignore-next-line
    class StringTemplateRenderingException extends \Shopware\Core\Framework\Adapter\Twig\Exception\StringTemplateRenderingException
    {
    }
} else {
    class StringTemplateRenderingException extends ShopwareHttpException
    {
        public function __construct(string $twigMessage)
        {
            parent::__construct(
                'Failed rendering string template using Twig: {{ message }}',
                ['message' => $twigMessage]
            );
        }

        public function getErrorCode(): string
        {
            return 'FRAMEWORK__STRING_TEMPLATE_RENDERING_FAILED';
        }

        public function getStatusCode(): int
        {
            return Response::HTTP_BAD_REQUEST;
        }
    }
}

