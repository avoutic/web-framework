<?php

namespace WebFramework\Core;

use Slim\Http\ServerRequest as Request;
use WebFramework\Exception\InvalidCaptchaException;

class CaptchaService
{
    public function __construct(
        private Instrumentation $instrumentation,
        private RecaptchaFactory $captchaFactory,
    ) {
    }

    public function hasValidCaptcha(Request $request): bool
    {
        $span = $this->instrumentation->startSpan('captcha.check');
        $captcha = $this->captchaFactory->getRecaptcha();
        $captchaResponse = $request->getParam('g-recaptcha-response', '');

        if (!strlen($captchaResponse))
        {
            $this->instrumentation->finishSpan($span);

            return false;
        }

        if (!$captcha->verifyResponse($captchaResponse))
        {
            throw new InvalidCaptchaException();
        }

        $this->instrumentation->finishSpan($span);

        return true;
    }
}
