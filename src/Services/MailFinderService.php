<?php declare(strict_types=1);

namespace Frosh\TemplateMail\Services;

use Doctrine\DBAL\Connection;
use Frosh\TemplateMail\Services\TemplateMailContext;
use Frosh\TemplateMail\Services\MailLoader\LoaderInterface;
use Shopware\Core\Framework\Adapter\Translation\AbstractTranslator;
use Shopware\Core\Framework\Adapter\Translation\Translator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Language\LanguageEntity;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Twig\Loader\FilesystemLoader;

#[AsAlias]
class MailFinderService implements MailFinderServiceInterface
{
    final public const TYPE_HTML = 'html.';
    final public const TYPE_PLAIN = 'plain.';
    final public const TYPE_SUBJECT = 'subject.';

    /**
     * @param LoaderInterface[] $availableLoaders
     */
    public function __construct(
        #[Autowire(service: 'twig.loader.native_filesystem')]
        private readonly FilesystemLoader $filesystemLoader,
        #[TaggedIterator('frosh_template_mail.loader')]
        private readonly iterable $availableLoaders,
        private readonly EntityRepository $languageRepository,
        #[Autowire(service: Translator::class)]
        private readonly AbstractTranslator $translator,
        private readonly SearchPathProvider $searchPathProvider,
        private readonly Connection $connection
    ) {
    }

    public function findTemplateByTechnicalName(
        string $type,
        string $technicalName,
        TemplateMailContext $businessEvent,
        bool $returnFolder = false
    ): ?string {
        $paths = $this->filesystemLoader->getPaths();

        $searchFolder = $this->searchPathProvider->buildPaths($businessEvent);

        $stmt = $this->connection->prepare(
            'SELECT IFNULL(a.path, p.path) AS `path` FROM `theme` AS t '
                    . 'LEFT JOIN `theme_sales_channel` AS tsc ON tsc.`theme_id` = t.`id` '
                    . 'LEFT JOIN `plugin` AS p ON p.`name` = t.`technical_name` '
                    . 'LEFT JOIN `app` AS a ON a.`name` = t.`technical_name` '
                    . 'WHERE tsc.`sales_channel_id` = ?;'
        );

        $stmt->bindValue(1, Uuid::fromHexToBytes($businessEvent->getSalesChannelId()));
        $themePath = $stmt->executeQuery()->fetchOne();

        if (\is_string($themePath)) {
            usort($paths, function ($a, $b) use ($themePath) {
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
                            $this->fixTranslator($businessEvent);

                            return $returnFolder ? $filePath : $content;
                        }
                    }
                }
            }
        }

        return null;
    }

    private function fixTranslator(TemplateMailContext $businessEvent): void
    {
        $criteria = new Criteria([$businessEvent->getContext()->getLanguageId()]);
        $criteria->addAssociation('locale');

        /** @var LanguageEntity $language */
        $language = $this->languageRepository->search($criteria, $businessEvent->getContext())->first();

        $localCode = $language->getLocale()?->getCode();
        if ($localCode === null) {
            return;
        }

        $this->translator->injectSettings(
            $businessEvent->getSalesChannelId(),
            $businessEvent->getContext()->getLanguageId(),
            $localCode,
            $businessEvent->getContext()
        );
    }
}
