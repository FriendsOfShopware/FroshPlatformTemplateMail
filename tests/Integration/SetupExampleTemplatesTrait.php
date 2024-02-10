<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Tests\Integration;

use Symfony\Component\Filesystem\Filesystem;

trait SetupExampleTemplatesTrait
{
    /**
     * @var array<string, string>
     */
    private array $tplFiles = [
        'global/order_confirmation_mail/html.twig' => 'HTML CONFIRM',
        'global/order_confirmation_mail/plain.twig' => 'TEXT CONFIRM',
        'global/order_confirmation_mail/subject.twig' => 'SUBJECT CONFIRM',
    ];

    /**
     * @before
     */
    public function setupTemplates(): void
    {
        $fs = new Filesystem();

        $resources = $this->getResourcesFolder();
        foreach ($this->tplFiles as $tplFile => $content) {
            $file = $resources . '/' . $tplFile;
            $folder = \dirname($file);
            $fs->mkdir($folder);

            $fs->dumpFile($file, $content);
        }
    }

    /**
     * @after
     */
    public function removeTemplates(): void
    {
        $fs = new Filesystem();

        $resources = $this->getResourcesFolder();
        foreach ($this->tplFiles as $tplFile => $content) {
            $file = $resources . '/' . $tplFile;
            $fs->remove($file);
        }
    }

    private function getResourcesFolder(): string
    {
        return \dirname(__DIR__, 2) . '/src/Resources/views/email/';
    }
}
