<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class ExportCommand extends Command
{
    protected static $defaultName = 'frosh:template-mail:export';
    protected static $defaultDescription = 'Export mail templates from database to file system';

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->connection = $connection;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('directory', InputArgument::REQUIRED, 'Target directory');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $directory = $input->getArgument('directory');

        $query = <<<'SQL'
SELECT 
    mail_template_translation.content_plain, 
    mail_template_translation.content_html, 
    mail_template_translation.subject, 
    mail_template_type.technical_name, 
    locale.code AS locale
FROM mail_template_translation
INNER JOIN mail_template ON mail_template.id = mail_template_translation.mail_template_id
INNER JOIN mail_template_type ON mail_template_type.id = mail_template.mail_template_type_id
INNER JOIN `language` ON `language`.id = mail_template_translation.language_id
INNER JOIN locale ON locale.id = language.locale_id
SQL;
        $records = $this->connection->fetchAllAssociative($query);

        $fs = new Filesystem();

        foreach ($records as $record) {
            $templateDir = sprintf('%s/%s/%s', $directory, $record['locale'], $record['technical_name']);

            $map = [
                'content_plain' => 'plain',
                'content_html' => 'html',
                'subject' => 'subject',
            ];
            foreach ($map as $field => $name) {
                $targetFile = sprintf('%s/%s.twig', $templateDir, $name);

                $fs->dumpFile($targetFile, $record[$field]);
            }
        }

        $output->writeln('Files has been exported');

        return self::SUCCESS;
    }
}
