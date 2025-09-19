<?php

namespace App\Tests\Messenger\AlphagovNotify;

use Alphagov\Notifications\Client;
use Alphagov\Notifications\Exception\ApiException;
use App\Messenger\AlphagovNotify\LoginEmail;
use App\Messenger\AlphagovNotify\LoginMessageHandler;
use App\Messenger\AlphagovNotify\Templates;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

class LoginMessageHandlerTest extends TestCase
{
    /** @var Client&MockObject */
    private Client $alphagovNotify;
    private LoginMessageHandler $handler;

    protected function setUp(): void
    {
        $this->alphagovNotify = $this->createMock(Client::class);
        $this->handler = new LoginMessageHandler($this->alphagovNotify);
    }

    /**
     * @dataProvider successfulEmailProvider
     */
    public function testInvokeSuccess(string $email, array $personalisation, array $expectedResponse): void
    {
        $message = new LoginEmail($email, $personalisation);

        $this->alphagovNotify->expects($this->once())
            ->method('sendEmail')
            ->with($email, Templates::LOGIN_LINK, $personalisation)
            ->willReturn($expectedResponse);

        $result = ($this->handler)($message);

        $this->assertSame($expectedResponse, $result);
    }

    public function successfulEmailProvider(): array
    {
        return [
            'basic success' => [
                'test@example.com',
                ['link' => 'https://example.com/login/token'],
                [
                    'id' => 'notification-123',
                    'reference' => null,
                    'content' => ['subject' => 'Login Link'],
                    'uri' => 'https://api.notifications.service.gov.uk/v2/notifications/notification-123',
                ]
            ],
            'empty personalisation' => [
                'test@example.com',
                [],
                ['id' => 'notification-123']
            ],
            'complex personalisation' => [
                'user@example.com',
                [
                    'link' => 'https://example.com/login/abc123',
                    'username' => 'John Doe',
                    'expires_at' => '2023-12-31 23:59:59',
                ],
                ['id' => 'notification-456']
            ],
            'unicode email' => [
                'tëst@example.com',
                ['username' => 'Jöhn Döe'],
                ['id' => 'notification-unicode']
            ],
            'long email' => [
                str_repeat('a', 50) . '@example.com',
                ['link' => 'https://example.com/login?token=abc&user=123'],
                ['id' => 'notification-long']
            ]
        ];
    }

    /**
     * @dataProvider apiExceptionProvider
     */
    public function testInvokeWithApiExceptions(
        int $code,
        string $message,
        string $expectedException,
        ?string $expectedMessage = null,
        ?int $expectedCode = null
    ): void {
        $email = 'test@example.com';
        $loginMessage = new LoginEmail($email);

        $response = $this->createMock(ResponseInterface::class);
        $body = ['errors' => [['error' => 'error', 'message' => $message]]];
        $apiException = new ApiException($message, $code, $body, $response);

        $this->alphagovNotify->expects($this->once())
            ->method('sendEmail')
            ->willThrowException($apiException);

        $this->expectException($expectedException);
        if ($expectedMessage) {
            $this->expectExceptionMessage($expectedMessage);
        }
        if ($expectedCode !== null) {
            $this->expectExceptionCode($expectedCode);
        }

        ($this->handler)($loginMessage);
    }

    public function apiExceptionProvider(): array
    {
        return [
            'bad request 400' => [
                400,
                'Bad Request',
                UnrecoverableMessageHandlingException::class
            ],
            'forbidden 403' => [
                403,
                'Forbidden',
                UnrecoverableMessageHandlingException::class
            ],
            'unauthorized 401' => [
                401,
                'Unauthorized',
                ApiException::class,
                'Unauthorized',
                401
            ],
            'not found 404' => [
                404,
                'Not Found',
                ApiException::class,
                'Not Found',
                404
            ],
            'too many requests 429' => [
                429,
                'Too Many Requests',
                ApiException::class,
                'Too Many Requests',
                429
            ],
            'internal server error 500' => [
                500,
                'Internal Server Error',
                ApiException::class,
                'Internal Server Error',
                500
            ],
            'bad gateway 502' => [
                502,
                'Bad Gateway',
                ApiException::class,
                'Bad Gateway',
                502
            ],
            'service unavailable 503' => [
                503,
                'Service Unavailable',
                ApiException::class,
                'Service Unavailable',
                503
            ]
        ];
    }
}
