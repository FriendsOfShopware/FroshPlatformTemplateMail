<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Tests\Services\MjmlRenderer;

use Frosh\TemplateMail\Exception\MjmlCompileError;
use Frosh\TemplateMail\Services\MjmlRenderer\ApiMjmlRenderer;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ApiMjmlRendererTest extends TestCase
{
    public function testRenderingWorks(): void
    {
        $renderer = new ApiMjmlRenderer('https://mjml.shyim.de', new NullLogger());

        $mjml = file_get_contents(__DIR__ . '/../MailLoader/_fixtures/test.mjml');
        static::assertIsString($mjml);

        $html = $renderer->render($mjml);
        static::assertStringContainsString('<!doctype html>', $html);
        static::assertStringContainsString('<tbody>', $html);
    }

    public function testApiIsNotAvailable(): void
    {
        $mock = new MockHandler([
            new ServerException('Error Communicating with Server', new Request('GET', 'test'), new Response(500)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $renderer = new ApiMjmlRenderer('https://mjml.shyim.de', new NullLogger(), $client);

        static::assertSame('', $renderer->render('<mjml><mj-body></mj-body></mjml>'));
    }

    public function testApiRespondsErrors(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['errors' => ['some error happend']], JSON_THROW_ON_ERROR)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $renderer = new ApiMjmlRenderer('https://mjml.shyim.de', new NullLogger(), $client);

        static::expectException(MjmlCompileError::class);
        $renderer->render('<mjml><mj-body></mj-body></mjml>');
    }

    public function testEmptyResponseReturnsEmptyString(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '""'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $renderer = new ApiMjmlRenderer('https://mjml.shyim.de', new NullLogger(), $client);

        static::assertSame('', $renderer->render('<mjml><mj-body></mj-body></mjml>'));
    }
}
