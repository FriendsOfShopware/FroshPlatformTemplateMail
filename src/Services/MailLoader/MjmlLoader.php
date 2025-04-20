<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Services\MailLoader;

use Frosh\TemplateMail\Exception\MjmlCompileError;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class MjmlLoader implements LoaderInterface
{
    private const MJML_INCLUDE = '/<mj-include.*?path=[\'|\"]([^"|\']*)[^>]*\/>/im';

    public function __construct(
        #[Autowire('%frosh_platform_template_mail.mjml_server%')]
        private readonly string $mjmlServer,
        private readonly LoggerInterface $logger,
        private readonly Client $client = new Client(),
    ) {
    }

    /**
     * @throws GuzzleException
     */
    public function load(string $path): string
    {
        $fileContent = file_get_contents($path);
        if ($fileContent === false) {
            // Return empty string to load shopware default templates.
            return '';
        }

        $mjmlTemplate = $this->parseIncludes($fileContent, \dirname($path));

        try {
            $response = $this->client->post($this->mjmlServer, [
                'json' => [
                    'mjml' => $mjmlTemplate,
                ],
            ]);
        } catch (ServerException $e) {
            $this->logger->critical('MJML Api is not accessible', ['response' => $e->getResponse()->getBody(), 'code' => $e->getResponse()->getStatusCode()]);

            // Return empty string to load shopware default templates.
            return '';
        }

        /** @var array{errors?: array<string>, html?: string}|string $compileTemplate */
        $compileTemplate = json_decode($response->getBody()->getContents(), true, 512, \JSON_THROW_ON_ERROR);

        if (empty($compileTemplate) || !\is_array($compileTemplate)) {
            // Return empty string to load shopware default templates.
            return '';
        }

        if (\array_key_exists('errors', $compileTemplate) && !empty($compileTemplate['errors'])) {
            foreach ($compileTemplate['errors'] as $error) {
                $this->logger->critical('Error during compiling of MJML templates', ['response' => $error]);
            }

            throw new MjmlCompileError(implode('\n', $compileTemplate['errors']));
        }

        return $compileTemplate['html'];
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
