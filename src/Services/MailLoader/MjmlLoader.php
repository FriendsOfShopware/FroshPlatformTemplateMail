<?php

namespace Frosh\TemplateMail\Services\MailLoader;

use Frosh\TemplateMail\Exception\MjmlCompileError;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Exception\GuzzleException;

class MjmlLoader implements LoaderInterface
{
    const MJML_INCLUDE = '/<mj-include.*?path=[\'|\"]([^"|\']*)[^>]*\/>/im';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * @param LoggerInterface $logger
     * @param CacheItemPoolInterface $cache
     *
     * @param Client|null $client
     */
    public function __construct(LoggerInterface $logger, CacheItemPoolInterface $cache, Client $client = null)
    {
        $this->client = $client ?? new Client();
        $this->logger = $logger;
        $this->cache = $cache;
    }

    /**
     * @param string $path
     * @return string
     *
     * @throws GuzzleException
     */
    public function load(string $path): string
    {
        $content = $this->renderMjmlTemplate($path);

        if (!empty($content['errors'])) {
            foreach ($content['errors'] as $error) {
                throw new MjmlCompileError($error);
            }
        }

        return $content['html'];
    }

    public function supportedExtensions(): array
    {
        return ['mjml'];
    }

    /**
     * @param $string
     * @param $folder
     *
     * @return array|mixed|string|string[]
     */
    private function parseIncludes($string, $folder)
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
    /**
     * @param string $path
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function renderMjmlTemplate(string $path)
    {
        $mjmlTemplate = $this->parseIncludes(file_get_contents($path), dirname($path));
        $cacheKey = 'mjml' . md5($mjmlTemplate);
        $cacheItem = $this->cache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }
        try {
            $response = $this->client->post('https://mjml.shyim.de', [
                'json' => [
                    'mjml' => $mjmlTemplate
                ]
            ]);
        } catch (ServerException $e) {
            $this->logger->critical('MJML Api is not accessible', ['response' => $e->getResponse()->getBody(), 'code' => $e->getResponse()->getStatusCode()]);
            throw $e;
        }

        $compileTemplate = json_decode($response->getBody()->getContents(), true);

        if(is_null($compileTemplate)) {
            $compileTemplate = ""; // ToDo get default mail template.
        }

        $cacheItem->set($compileTemplate);
        $this->cache->save($cacheItem);

        return $compileTemplate;
    }
}
