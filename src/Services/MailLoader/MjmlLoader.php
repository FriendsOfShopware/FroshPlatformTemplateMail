<?php

namespace Frosh\TemplateMail\Services\MailLoader;

use Frosh\TemplateMail\Exception\MjmlCompileError;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Exception\GuzzleException;

class MjmlLoader implements LoaderInterface
{
    private const MJML_INCLUDE = '/<mj-include.*?path=[\'|\"]([^"|\']*)[^>]*\/>/im';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     *
     * @param Client|null $client
     */
    public function __construct(LoggerInterface $logger, Client $client = null)
    {
        $this->client = $client ?? new Client();
        $this->logger = $logger;
    }

    /**
     * @param string $path
     * @return string
     *
     * @throws GuzzleException
     */
    public function load(string $path): string
    {
        $mjmlTemplate = $this->parseIncludes(file_get_contents($path), dirname($path));

        try {
            $response = $this->client->post('https://mjml.shyim.de', [
                'json' => [
                    'mjml' => $mjmlTemplate
                ]
            ]);
        } catch (ServerException $e) {
            $this->logger->critical('MJML Api is not accessible', ['response' => $e->getResponse()->getBody(), 'code' => $e->getResponse()->getStatusCode()]);

            // Return empty string to load shopware default templates.
            return '';
        }

        $compileTemplate = json_decode($response->getBody()->getContents(), true);

        if (is_null($compileTemplate) || count($compileTemplate) === 0) {
            // Return empty string to load shopware default templates.
            return '';
        }

        if (!empty($compileTemplate['errors'])) {
            foreach ($compileTemplate['errors'] as $error) {
                $this->logger->critical('Error during compiling of MJML templates', ['response' => $error]);
            }

            throw new MjmlCompileError(implode('\n', $compileTemplate['errors']));
        }

        return $compileTemplate['html'];
    }

    public function supportedExtensions(): array
    {
        return ['mjml'];
    }

    private function parseIncludes(string $string, string $folder): string
    {
        preg_match_all(self::MJML_INCLUDE, $string, $matches);

        if (!empty($matches)) {
            foreach ($matches[0] as $key => $match) {
                if (strpos($matches[1][$key], 'mjml') === false) {
                    $matches[1][$key] .= '.mjml';
                }

                $fileName = $folder . '/' . $matches[1][$key];

                if (!file_exists($fileName)) {
                    throw new MjmlCompileError(sprintf('File with name "%s", could not be found in path "%s"', $matches[1][$key], $fileName));
                }

                $string = str_replace($match, file_get_contents($fileName), $string);
            }
        }

        return $string;
    }
}
