<?php

namespace WebFramework\Core;

use Slim\Http\ServerRequest as Request;
use WebFramework\Exception\InvalidCaptchaException;

/**
 * Class CaptchaService.
 *
 * Handles CAPTCHA validation for the application.
 */
class CaptchaService
{
    /**
     * CaptchaService constructor.
     *
     * @param Instrumentation  $instrumentation The instrumentation service for performance tracking
     * @param RecaptchaFactory $captchaFactory  The factory for creating ReCAPTCHA instances
     */
    public function __construct(
        private Instrumentation $instrumentation,
        private RecaptchaFactory $captchaFactory,
    ) {}

    /**
     * Validate the CAPTCHA response from a request.
     *
     * @param Request $request The request containing the CAPTCHA response
     *
     * @return bool True if the CAPTCHA is valid, false otherwise
     *
     * @throws InvalidCaptchaException If the CAPTCHA response is invalid
     */
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
