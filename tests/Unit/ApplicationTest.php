<?php

declare(strict_types=1);

namespace CaptchaLocalResolver\Tests\Unit;

use CaptchaLocalResolver\Application;
use CaptchaLocalResolver\Captchas;
use CaptchaLocalResolver\Tests\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @covers \CaptchaLocalResolver\Application
 */
class ApplicationTest extends TestCase
{
    public function testConstructWithoutParameters(): void
    {
        $app = new Application();
        $this->assertSame($app->getRepository(), $app->getRepository(), 'repository should be always the same');
        $this->assertStringStartsWith(dirname(__DIR__, 2), $app->getWebroot(), 'default webroot must be similar');
    }

    public function testConstructWitParameters(): void
    {
        /** @var Captchas $repository */
        $repository = $this->createMock(Captchas::class);
        $webroot = __DIR__;

        $app = new Application($repository, $webroot);

        $this->assertSame($repository, $app->getRepository(), 'repository should be the same as provided');
        $this->assertSame($webroot, $app->getWebroot(), 'webroot should be the same as provided');
    }

    public function testInvoke(): void
    {
        $app = new Application();
        $this->assertIsCallable($app);

        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $response = $app($serverRequest);

        $this->assertSame(500, $response->getStatusCode(), 'Expected to not be able to run a fake server request');
        $this->assertSame('text/plain; charset=utf-8', $response->getHeaderLine('content-type'));
    }

    public function testServeNonExistentRoute(): void
    {
        $app = new Application();
        $request = $this->createRequest('GET', 'http://localhost/non-existent');

        $response = $app->serve($request);
        $this->assertSame(404, $response->getStatusCode(), 'Non existent route return 404');
        $this->assertSame('text/plain; charset=utf-8', $response->getHeaderLine('content-type'));
    }

    public function testServeInvalidVerb(): void
    {
        $app = new Application();
        $request = $this->createRequest('POST', 'http://localhost/');

        $response = $app->serve($request);
        $this->assertSame(405, $response->getStatusCode(), 'Existent route with invalid verb return 405');
        $this->assertSame('text/plain; charset=utf-8', $response->getHeaderLine('content-type'));
    }

    public function testServeInvalidParameter(): void
    {
        $app = new Application();
        $request = $this->createRequest('POST', 'http://localhost/send-image');

        $response = $app->serve($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('text/plain; charset=utf-8', $response->getHeaderLine('content-type'));
    }

    public function testServerObtainParsedBodyNull(): void
    {
        $app = new Application();
        $request = $this->createRequest('GET', 'http://localhost/', null);
        $response = $app->serve($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testServerObtainParsedBodyObject(): void
    {
        $app = new Application();
        $request = $this->createRequest('GET', 'http://localhost/', (object) []);
        $response = $app->serve($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testServeRespondsWithHtmlText(): void
    {
        $app = new Application();
        $request = $this->createRequest('GET', 'http://localhost/');

        $response = $app->serve($request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html; charset=utf-8', $response->getHeaderLine('content-type'));
    }

    public function testServeRespondsWithApplicationJson(): void
    {
        $app = new Application();
        $request = $this->createRequest('GET', 'http://localhost/captchas');

        $response = $app->serve($request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/json; charset=utf-8', $response->getHeaderLine('content-type'));
    }

    public function testServeRespondsWithTextPlain(): void
    {
        $app = new Application();
        $repository = $app->getRepository();
        $code = $repository->push(base64_encode('x-image'))->getCode();
        $request = $this->createRequest('POST', 'http://localhost/set-code-answer', [
            'code' => $code,
            'answer' => 'x-answer',
        ]);

        $response = $app->serve($request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/plain; charset=utf-8', $response->getHeaderLine('content-type'));
    }
}
