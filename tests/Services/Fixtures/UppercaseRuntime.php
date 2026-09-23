<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Tests\Services\Fixtures;

class UppercaseRuntime
{
    public function uppercase(string $text): string
    {
        return mb_strtoupper($text);
    }
}
