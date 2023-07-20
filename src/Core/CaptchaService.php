<?php

namespace WebFramework\Core;

use Slim\Http\ServerRequest as Request;
use WebFramework\Exception\InvalidCaptchaException;

class CaptchaService
{
    public function __construct(
        protected RecaptchaFactory $captchaFactory,
    ) {
    }

    public function hasValidCaptcha(Request $request): bool
    {
        $captcha = $this->captchaFactory->getRecaptcha();
        $captchaResponse = $request->getParam('g-recaptcha-response', '');

        if (!strlen($captchaResponse))
        {
            return false;
        }

        if (!$captcha->verifyResponse($captchaResponse))
        {
            throw new InvalidCaptchaException();
        }

        return true;
    }
}
