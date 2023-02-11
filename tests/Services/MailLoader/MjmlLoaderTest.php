<?php
declare(strict_types=1);

namespace Frosh\TemplateMail\Tests\Services\MailLoader;

use Frosh\TemplateMail\Exception\MjmlCompileError;
use Frosh\TemplateMail\Services\MailLoader\MjmlLoader;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class MjmlLoaderTest extends TestCase
{
    public function testLoadingWorks(): void
    {
        $loader = new MjmlLoader(new NullLogger());
        static::assertSame(['mjml'], $loader->supportedExtensions());

        $text = $loader->load(__DIR__ . '/_fixtures/test.mjml');
        static::assertStringContainsString('<!doctype html>', $text);
        static::assertStringContainsString('<tbody>', $text);
    }

    public function testApiIsNotAvailable(): void
    {
        $mock = new MockHandler([
            new ServerException('Error Communicating with Server', new Request('GET', 'test'), new Response(500)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $loader = new MjmlLoader(new NullLogger(), $client);

        static::assertSame('', $loader->load(__DIR__ . '/_fixtures/test.mjml'));
    }

    public function testApiRespondsErrors(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['errors' => ['some error happend']])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $loader = new MjmlLoader(new NullLogger(), $client);

        static::expectException(MjmlCompileError::class);
        $loader->load(__DIR__ . '/_fixtures/test.mjml');
    }
}
