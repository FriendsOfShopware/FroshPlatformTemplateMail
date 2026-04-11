<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Services\MailLoader;

use Frosh\TemplateMail\Exception\MjmlCompileError;
use Frosh\TemplateMail\Services\MjmlRenderer\MjmlRendererInterface;

class MjmlLoader implements LoaderInterface
{
    private const MJML_INCLUDE = '/<mj-include.*?path=[\'|\"]([^"|\']*)[^>]*\/>/im';

    public function __construct(
        private readonly MjmlRendererInterface $mjmlRenderer,
    ) {
    }

    public function load(string $path): string
    {
        if (!file_exists($path)) {
            return '';
        }

        $fileContent = file_get_contents($path);
        if ($fileContent === false) {
            // Return empty string to load shopware default templates.
            return '';
        }

        $mjmlTemplate = $this->parseIncludes($fileContent, \dirname($path));

        return $this->mjmlRenderer->render($mjmlTemplate);
    }

    /**
     * @return string[]
     */
    public function supportedExtensions(): array
    {
        return ['mjml'];
    }

    private function parseIncludes(string $string, string $folder): string
    {
        preg_match_all(self::MJML_INCLUDE, $string, $matches);

        foreach ($matches[0] as $key => $match) {
            if (!str_contains((string) $matches[1][$key], 'mjml')) {
                $matches[1][$key] .= '.mjml';
            }

            $fileName = $folder . '/' . $matches[1][$key];

            if (!file_exists($fileName)) {
                throw new MjmlCompileError(\sprintf('File with name "%s", could not be found in path "%s"', $matches[1][$key], $fileName));
            }

            $string = str_replace($match, file_get_contents($fileName) ?: '', $string);
        }

        return $string;
    }
}
