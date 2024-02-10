<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Tests\Command;

use Doctrine\DBAL\Connection;
use Frosh\TemplateMail\Command\ExportCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ExportCommandTest extends TestCase
{
    public function testExport(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchAllAssociative')->willReturn([
            [
                'content_plain' => 'My content',
                'content_html' => '<h1>My content</h1>',
                'subject' => 'Test template',
                'technical_name' => 'test_template',
                'locale' => 'en',
            ],
        ]);

        $command = new ExportCommand($connection);

        $tester = new CommandTester($command);
        $tester->execute(['directory' => sys_get_temp_dir()]);

        $htmlFile = sys_get_temp_dir() . '/en/test_template/html.twig';
        $plainFile = sys_get_temp_dir() . '/en/test_template/plain.twig';
        $subjectFile = sys_get_temp_dir() . '/en/test_template/subject.twig';

        static::assertFileExists($htmlFile);
        static::assertFileExists($plainFile);
        static::assertFileExists($subjectFile);

        static::assertEquals('<h1>My content</h1>', file_get_contents($htmlFile));
        static::assertEquals('My content', file_get_contents($plainFile));
        static::assertEquals('Test template', file_get_contents($subjectFile));

        static::assertSame(0, $tester->getStatusCode());
        static::assertStringContainsString('Files has been exported', $tester->getDisplay());
    }
}
