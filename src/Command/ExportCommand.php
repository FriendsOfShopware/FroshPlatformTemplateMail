<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Command;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand('frosh:template-mail:export', 'Export mail templates from database to file system')]
class ExportCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $directory = $input->getArgument('directory');
        if (!is_string($directory)) {
            throw new \RuntimeException('Directory is not a string');
        }

        $query = <<<'SQL'
SELECT 
    mail_template_translation.content_plain, 
    mail_template_translation.content_html, 
    mail_template_translation.subject, 
    mail_template_type.technical_name, 
    mail_template.id,
    locale.code AS locale
FROM mail_template_translation
INNER JOIN mail_template ON mail_template.id = mail_template_translation.mail_template_id
INNER JOIN mail_template_type ON mail_template_type.id = mail_template.mail_template_type_id
INNER JOIN `language` ON `language`.id = mail_template_translation.language_id
INNER JOIN locale ON locale.id = language.locale_id
SQL;
        $records = $this->connection->fetchAllAssociative($query);

        $fs = new Filesystem();

        /** @var array{content_plain: string, content_html: string, subject: string, technical_name: string, id: string, locale: string} $record */
        foreach ($records as $record) {
            $templateDir = sprintf('%s/%s/%s', $directory, $record['locale'], $record['technical_name']);

            $map = [
                'content_plain' => 'plain',
                'content_html' => 'html',
                'subject' => 'subject',
            ];
            foreach ($map as $field => $name) {
                if ($input->getOption('template-id')) {
                    $targetFile = sprintf('%s/%s/%s.twig', $templateDir, Uuid::fromBytesToHex($record['id']), $name);
                } else {
                    $targetFile = sprintf('%s/%s.twig', $templateDir, $name);
                }

                $fs->dumpFile($targetFile, $record[$field]);
            }
        }

        $output->writeln('Files has been exported');

        return self::SUCCESS;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('directory', InputArgument::REQUIRED, 'Target directory')
            ->addOption('template-id', 't', InputOption::VALUE_NONE, 'Create subfolders per template UUID, to allow for multiple templates of the same type');
    }
}
