<?php

namespace Tests\Unit;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;
use Slim\Http\ServerRequest as Request;
use WebFramework\Diagnostics\Instrumentation;
use WebFramework\Exception\InvalidCaptchaException;
use WebFramework\Security\CaptchaService;
use WebFramework\Security\Recaptcha;
use WebFramework\Security\RecaptchaFactory;

/**
 * @internal
 *
 * @covers \WebFramework\Security\CaptchaService
 * @covers \WebFramework\Security\Recaptcha
 */
final class RecaptchaTest extends Unit
{
    private function buildRecaptcha(string $body): Recaptcha
    {
        return new Recaptcha(
            $this->makeEmpty(LoggerInterface::class),
            $this->makeEmpty(Client::class, [
                'post' => new Response(200, [], $body),
            ]),
            'secret',
        );
    }

    public function testSuccessWithScoreAndAction()
    {
        $recaptcha = $this->buildRecaptcha('{"success": true, "score": 0.7, "action": "login"}');

        verify($recaptcha->verifyResponse('token'))->true();
        verify($recaptcha->getScore())->same(0.7);
        verify($recaptcha->getAction())->same('login');
    }

    public function testVersion2AnswerHasNoScoreOrAction()
    {
        $recaptcha = $this->buildRecaptcha('{"success": true}');

        verify($recaptcha->verifyResponse('token'))->true();
        verify($recaptcha->getScore())->null();
        verify($recaptcha->getAction())->null();
    }

    public function testFailure()
    {
        $recaptcha = $this->buildRecaptcha('{"success": false, "error-codes": ["timeout-or-duplicate"]}');

        verify($recaptcha->verifyResponse('token'))->false();
        verify($recaptcha->getErrorCodes())->same(['timeout-or-duplicate']);
    }

    public function testScoreOfAnEarlierVerificationIsNotKept()
    {
        $client = $this->makeEmpty(Client::class, [
            'post' => Expected::exactly(2, function () {
                static $calls = 0;

                return new Response(200, [], (++$calls === 1) ? '{"success": true, "score": 0.9, "action": "login"}' : '{"success": false}');
            }),
        ]);
        $recaptcha = new Recaptcha($this->makeEmpty(LoggerInterface::class), $client, 'secret');

        $recaptcha->verifyResponse('first');
        $recaptcha->verifyResponse('second');

        verify($recaptcha->getScore())->null();
        verify($recaptcha->getAction())->null();
    }

    public function testAnswerThatIsNotJsonIsNotVerified()
    {
        verify($this->buildRecaptcha('<html>Service unavailable</html>')->verifyResponse('token'))->false();
        verify($this->buildRecaptcha('{"score": 0.9}')->verifyResponse('token'))->false();
    }

    public function testEmptyResponseIsNotSentToGoogle()
    {
        $recaptcha = new Recaptcha(
            $this->makeEmpty(LoggerInterface::class),
            $this->makeEmpty(Client::class, ['post' => Expected::never()]),
            'secret',
        );

        verify($recaptcha->verifyResponse(''))->false();
    }

    /**
     * @param array<string, mixed> $recaptcha
     */
    private function buildCaptchaService(array $recaptcha, int &$started, int &$finished): CaptchaService
    {
        return new CaptchaService(
            $this->makeEmpty(Instrumentation::class, [
                'startSpan' => function () use (&$started) {
                    $started++;

                    return null;
                },
                'finishSpan' => function () use (&$finished) {
                    $finished++;
                },
            ]),
            $this->makeEmpty(RecaptchaFactory::class, [
                'getRecaptcha' => $this->makeEmpty(Recaptcha::class, $recaptcha),
            ]),
        );
    }

    public function testSpanIsFinishedOnEveryPath()
    {
        $request = $this->makeEmpty(Request::class, ['getParam' => 'token']);

        foreach ([
            ['verifyResponse' => true],
            ['verifyResponse' => false],
            ['verifyResponse' => function () { throw new \RuntimeException('Invalid reCAPTCHA key used'); }],
        ] as $recaptcha)
        {
            $started = 0;
            $finished = 0;
            $service = $this->buildCaptchaService($recaptcha, $started, $finished);

            try
            {
                $service->hasValidCaptcha($request);
            }
            catch (InvalidCaptchaException|\RuntimeException $e)
            {
            }

            verify($started)->equals(1);
            verify($finished)->equals(1);
        }
    }

    public function testInvalidCaptchaStillThrows()
    {
        $started = 0;
        $finished = 0;
        $service = $this->buildCaptchaService(['verifyResponse' => false], $started, $finished);

        verify(function () use ($service) {
            $service->hasValidCaptcha($this->makeEmpty(Request::class, ['getParam' => 'token']));
        })->callableThrows(InvalidCaptchaException::class);
    }
}
