<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Services;

use Doctrine\DBAL\Connection;
use Frosh\TemplateMail\Services\MailLoader\LoaderInterface;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Twig\Loader\FilesystemLoader;

#[AsAlias]
class MailFinderService implements MailFinderServiceInterface
{
    final public const TYPE_HTML = 'html.';
    final public const TYPE_PLAIN = 'plain.';
    final public const TYPE_SUBJECT = 'subject.';

    /**
     * @param LoaderInterface[] $availableLoaders
     * @param iterable<BundleInterface> $bundles
     */
    public function __construct(
        #[Autowire(service: 'twig.loader.native_filesystem')]
        private readonly FilesystemLoader $filesystemLoader,
        #[TaggedIterator('frosh_template_mail.loader')]
        private readonly iterable $availableLoaders,
        private readonly SearchPathProvider $searchPathProvider,
        private readonly Connection $connection,
        #[Autowire(service: 'kernel.bundles')]
        private readonly iterable $bundles,
    ) {}

    public function findTemplateByTechnicalName(
        string $type,
        string $technicalName,
        TemplateMailContext $businessEvent,
        bool $returnFolder = false,
    ): ?string {
        $paths = $this->filesystemLoader->getPaths();

        $searchFolder = $this->searchPathProvider->buildPaths($businessEvent);

        $themePath = $this->findPathOfThemeFromPluginOrApp($businessEvent->getSalesChannelId())
            ?? $this->findPathOfThemeFromSymfonyBundle($businessEvent->getSalesChannelId());

        if (\is_string($themePath)) {
            usort($paths, static function ($a, $b) use ($themePath) {
                if (str_contains($a, $themePath)) {
                    return -1;
                }

                if (str_contains($b, $themePath)) {
                    return 1;
                }

                return 0;
            });
        }

        foreach ($paths as $path) {
            foreach ($this->availableLoaders as $availableLoader) {
                $supportedExtensions = $availableLoader->supportedExtensions();

                foreach ($supportedExtensions as $supportedExtension) {
                    foreach ($searchFolder as $folder) {
                        $filePath = $path . '/email/' . $folder . '/' . $technicalName . '/' . $type . $supportedExtension;
                        if (file_exists($filePath) && $content = $availableLoader->load($filePath)) {
                            return $returnFolder ? $filePath : $content;
                        }
                    }
                }
            }
        }

        return null;
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

        $technicalName = $stmt->executeQuery()->fetchOne();

        if (isset($this->bundles[$technicalName])) {
            return $this->bundles[$technicalName]->getPath();
        }

        return null;
    }
}
