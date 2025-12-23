<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Services;

use Doctrine\DBAL\Connection;
use Frosh\TemplateMail\DTO\TemplateData;
use Frosh\TemplateMail\DTO\TemplateType;
use Frosh\TemplateMail\DTO\TypeData;
use Frosh\TemplateMail\Services\MailLoader\LoaderInterface;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Twig\Loader\FilesystemLoader;

#[AsAlias]
class MailFinderService implements MailFinderServiceInterface
{
    /**
     * @param LoaderInterface[] $availableLoaders
     * @param array<BundleInterface> $bundles
     */
    public function __construct(
        #[Autowire(service: 'twig.loader.native_filesystem')]
        private readonly FilesystemLoader $filesystemLoader,
        #[AutowireIterator('frosh_template_mail.loader')]
        private readonly iterable $availableLoaders,
        private readonly SearchPathProvider $searchPathProvider,
        private readonly Connection $connection,
        #[Autowire(service: 'kernel.bundles')]
        private readonly array $bundles,
    ) {
    }

    public function getTemplateDataByTechnicalName(
        string $technicalName,
        TemplateMailContext $businessEvent,
        ?string $mailTemplateId = null,
    ): TemplateData {
        $templateData = new TemplateData();

        $paths = $this->filesystemLoader->getPaths();

        $searchFolders = $this->searchPathProvider->buildPaths($businessEvent);

        $themePath = $this->findPathOfThemeFromPluginOrApp($businessEvent->getSalesChannelId())
            ?? $this->findPathOfThemeFromSymfonyBundle($businessEvent->getSalesChannelId());

        if (\is_string($themePath)) {
            \usort(
                $paths,
                static fn ($a, $b) => \str_contains($b, $themePath) <=> \str_contains($a, $themePath)
            );
        }

        foreach ($paths as $path) {
            $viewMailFolderPath = $path . '/email';
            if (!\is_dir($viewMailFolderPath)) {
                continue;
            }

            foreach ($this->availableLoaders as $loader) {
                $supportedExtensions = $loader->supportedExtensions();

                foreach ($supportedExtensions as $supportedExtension) {
                    foreach ($searchFolders as $folder) {
                        $folderPath = $viewMailFolderPath . '/' . $folder . '/' . $technicalName;
                        if (!\is_dir($folderPath)) {
                            continue;
                        }

                        $templateData->subject ??= $this->loadTypeData(TemplateType::SUBJECT, $loader, $supportedExtension, $folderPath, $mailTemplateId);
                        $templateData->html ??= $this->loadTypeData(TemplateType::HTML, $loader, $supportedExtension, $folderPath, $mailTemplateId);
                        $templateData->plain ??= $this->loadTypeData(TemplateType::PLAIN, $loader, $supportedExtension, $folderPath, $mailTemplateId);

                        if ($templateData->isAllSet()) {
                            return $templateData;
                        }
                    }
                }
            }
        }

        return $templateData;
    }

    public function findPathOfThemeFromPluginOrApp(string $salesChannelId): ?string
    {
        $stmt = $this->connection->prepare(
            'SELECT IFNULL(a.path, p.path) AS `path` FROM `theme` AS t '
            . 'LEFT JOIN `theme_sales_channel` AS tsc ON tsc.`theme_id` = t.`id` '
            . 'LEFT JOIN `plugin` AS p ON p.`name` = t.`technical_name` '
            . 'LEFT JOIN `app` AS a ON a.`name` = t.`technical_name` '
            . 'WHERE tsc.`sales_channel_id` = ?;',
        );

        $stmt->bindValue(1, Uuid::fromHexToBytes($salesChannelId));

        /** @var string|false $path */
        $path = $stmt->executeQuery()->fetchOne();

        if ($path === false) {
            return null;
        }

        return $path;
    }

    public function findPathOfThemeFromSymfonyBundle(string $salesChannelId): ?string
    {
        $stmt = $this->connection->prepare(
            'SELECT t.`technical_name` FROM `theme` AS t '
            . 'LEFT JOIN `theme_sales_channel` AS tsc ON tsc.`theme_id` = t.`id` '
            . 'WHERE tsc.`sales_channel_id` = ?;',
        );

        $stmt->bindValue(1, Uuid::fromHexToBytes($salesChannelId));

        /** @var string $technicalName */
        $technicalName = $stmt->executeQuery()->fetchOne();

        if (isset($this->bundles[$technicalName])) {
            return $this->bundles[$technicalName]->getPath();
        }

        return null;
    }

    private function loadTypeData(
        TemplateType $type,
        LoaderInterface $loader,
        string $supportedExtension,
        string $folderPath,
        ?string $mailTemplateId
    ): ?TypeData {
        $filePath = $folderPath . '/' . $mailTemplateId . '/' . $type->filePart() . $supportedExtension;
        if (\is_file($filePath) && $content = $loader->load($filePath)) {
            return new TypeData(
                $filePath,
                $content,
            );
        }

        $filePath = $folderPath . '/' . $type->filePart() . $supportedExtension;
        if (\is_file($filePath) && $content = $loader->load($filePath)) {
            return new TypeData(
                $filePath,
                $content,
            );
        }

        return null;
    }
}
