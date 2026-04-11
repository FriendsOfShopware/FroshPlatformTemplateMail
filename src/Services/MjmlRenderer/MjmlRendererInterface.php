<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Services\MjmlRenderer;

interface MjmlRendererInterface
{
    /**
     * Convert assembled MJML (includes already resolved) to HTML.
     *
     * @throws \Frosh\TemplateMail\Exception\MjmlCompileError
     */
    public function render(string $mjml): string;
}
