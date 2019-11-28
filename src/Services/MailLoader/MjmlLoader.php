<?php

namespace Frosh\TemplateMail\Services\MailLoader;

use Frosh\TemplateMail\Exception\MjmlCompileError;
use GuzzleHttp\Client;
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

    public function __construct(LoggerInterface $logger)
    {
        $this->client = new Client();
        $this->logger = $logger;
    }

    public function load(string $path): string
    {
        $response = $this->client->post('https://mjml.shyim.de', [
            'json' => [
                'mjml' => file_get_contents($path)
            ]
        ]);

        if ($response->getStatusCode() !== 200) {
            $this->logger->critical('MJML Api is not accessible', ['response' => $response->getBody(), 'code' => $response->getStatusCode()]);
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
