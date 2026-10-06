<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Tests\Services\Fixtures;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class UppercaseExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            // the callable is resolved through a runtime loader, like filters declared with #[AsTwigFilter]
            new TwigFilter('uppercase', [UppercaseRuntime::class, 'uppercase']),
        ];
    }
}
