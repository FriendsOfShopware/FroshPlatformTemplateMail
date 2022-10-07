<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Export mail template from database to file system
 */
class ExportCommand extends AbstractCommand
{
    /**
     * Database connection
     *
     * @var \Doctrine\DBAL\Connection
     */
    protected $databaseConnection;

    /**
     * Root directory
     *
     * @var string
     */
    protected $directory;

    /**
     * Filesystem
     *
     * @var \League\Flysystem\Filesystem
     */
    protected $filesystem;

    /**
     * Set repository for mail templates.
     *
     * @param \Doctrine\DBAL\Connection $databaseConnection
     */
    public function setDatabaseConnection(\Doctrine\DBAL\Connection $databaseConnection): void
    {
        $this->databaseConnection = $databaseConnection;
    }

    /**
     * @inerhitDoc
     */
    protected function configure()
    {
        $this
            ->setName('frosh:template-mail:export')
            ->setDescription('Export mail templates from database to file system')
            ->addArgument('directory', InputArgument::REQUIRED, 'Target directory')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dry run (only show what is being done)');
    }

    /**
     * @inerhitDoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        // Print title.
        $this->io->title(sprintf('%s (%s)', $this->getDescription(), $this->getName()));

        // Get dry-run flag.
        $isDryRun = (bool)$input->getOption('dry-run');

        // Handle directory.
        $this->directory = $input->getArgument('directory');
        if (!$isDryRun && !$this->initializeFilesystem()) {
            return 1;
        }

        // Fetch mail templates.
        $query = <<<EOT
SELECT mail_template_translation.*, mail_template_type.technical_name, locale.code AS locale
FROM mail_template_translation
JOIN mail_template ON mail_template.id = mail_template_translation.mail_template_id
JOIN mail_template_type ON mail_template_type.id = mail_template.mail_template_type_id
JOIN language ON language.id = mail_template_translation.language_id
JOIN locale ON locale.id = language.locale_id
EOT;
        $records = $this->databaseConnection->fetchAllAssociative($query);

        // Write mail templates to disk.
        foreach ($records as $record) {
            if (!$this->writeMailTemplate($record, $isDryRun)) {
                return 1;
            }
        }

        return 0;
    }

    /**
     * Initialize filesystem.
     *
     * @return bool
     */
    protected function initializeFilesystem(): bool
    {
        // Make sure directory exists.
        if (!is_dir($this->directory)) {
            if (!@mkdir($this->directory, 0755, true) && !is_dir($this->directory)) {
                $this->io->error(sprintf('Directory "%s" was not created', $this->directory));

                return false;
            }
        }

        // Initialize filesystem.
        $this->filesystem = new \League\Flysystem\Filesystem(new \League\Flysystem\Adapter\Local($this->directory));
        if (!$this->filesystem) {
            $this->io->error('Filesystem could not be initialized');

            return false;
        }

        return true;
    }

    /**
     * Write mail template to disk.
     *
     * @param array $record
     * @param bool $isDryRun
     * @return bool
     */
    protected function writeMailTemplate(array $record, bool $isDryRun): bool
    {
        // Create directory.
        $targetDirectory = sprintf('%s/%s', $record['locale'], $record['technical_name']);
        if (!$isDryRun && !$this->filesystem->createDir($targetDirectory)) {
            $this->io->error(sprintf('Directory "%s/%s" was not created', $this->directory, $targetDirectory));

            return false;
        }

        // Write template parts.
        $map = [
            'content_plain' => 'plain',
            'content_html' => 'html',
            'subject' => 'subject',
        ];
        foreach ($map as $field => $name) {
            $targetFile = sprintf('%s/%s.twig', $targetDirectory, $name);

            $this->io->writeln(
                sprintf(
                    '<info>Writing part "%s" of template "%s" to file "%s/%s"</info>',
                    $name,
                    $record['technical_name'],
                    $this->directory,
                    $targetFile
                )
            );

            if ($isDryRun) {
                continue;
            }

            if (!$this->filesystem->put($targetFile, $record[$field])) {
                $this->io->error(
                    sprintf('Part "%s" of template "%s" was not exported', $name, $record['technical_name'])
                );

                return false;
            }
        }

        return true;
    }
}
