<?php

namespace Frosh\TemplateMail\Services\MailLoader;

use Frosh\TemplateMail\Exception\MjmlCompileError;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use Psr\Log\LoggerInterface;

class MjmlLoader implements LoaderInterface
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger, Client $client = null)
    {
        $this->client = $client ?? new Client();
        $this->logger = $logger;
    }

    public function load(string $path): string
    {
        try {
            $response = $this->client->post('https://mjml.shyim.de', [
                'json' => [
                    'mjml' => file_get_contents($path)
                ]
            ]);
        } catch (ServerException $e) {
            $this->logger->critical('MJML Api is not accessible', ['response' => $e->getResponse()->getBody(), 'code' => $e->getResponse()->getStatusCode()]);

            throw $e;
        }

        $content = json_decode($response->getBody()->getContents(), true);

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
}
