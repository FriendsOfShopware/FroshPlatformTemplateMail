<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Services\MjmlRenderer;

use Frosh\TemplateMail\Exception\MjmlCompileError;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ApiMjmlRenderer implements MjmlRendererInterface
{
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
    public function render(string $mjml): string
    {
        try {
            $response = $this->client->post($this->mjmlServer, [
                'json' => [
                    'mjml' => $mjml,
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
}
