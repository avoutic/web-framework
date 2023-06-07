<?php

namespace WebFramework\Actions;

use WebFramework\Core\PageAction;
use WebFramework\Core\Recaptcha;
use WebFramework\Core\UserEmailService;
use WebFramework\Core\UserService;
use WebFramework\Entity\User;
use WebFramework\Repository\UserRepository;

class Register extends PageAction
{
    protected UserEmailService $userEmailService;
    protected UserRepository $userRepository;
    protected UserService $userService;

    public function init(): void
    {
        parent::init();

        $this->userEmailService = $this->container->get(UserEmailService::class);
        $this->userRepository = $this->container->get(UserRepository::class);
        $this->userService = $this->container->get(UserService::class);
    }

    /**
     * @return array<string, string>
     */
    public static function customGetFilter(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    public static function getFilter(): array
    {
        $customFilter = static::customGetFilter();

        $baseFilter = [
            'username' => FORMAT_USERNAME,
            'password' => FORMAT_PASSWORD,
            'password2' => FORMAT_PASSWORD,
            'email' => FORMAT_EMAIL,
            'accept_terms' => '0|1',
            'g-recaptcha-response' => '.*',
        ];

        return array_merge($baseFilter, $customFilter);
    }

    protected function checkSanity(): void
    {
        $recaptchaConfig = $this->getConfig('security.recaptcha');
        $this->verify(strlen($recaptchaConfig['site_key']), 'Missing reCAPTCHA Site Key');
        $this->verify(strlen($recaptchaConfig['secret_key']), 'Missing reCAPTCHA Secret Key');
    }

    protected function getTitle(): string
    {
        return 'Register new account';
    }

    protected function getOnload(): string
    {
        return "$('#username').focus();";
    }

    /**
     * @return array<mixed>
     */
    protected function getAfterVerifyData(): array
    {
        return [];
    }

    protected function customPreparePageContent(): void
    {
    }

    protected function customValueCheck(): bool
    {
        return true;
    }

    protected function customFinalizeCreate(User $user): void
    {
    }

    // Can be overriden for project specific user factories and user classes
    //
    protected function createUser(string $username, string $password, string $email): User
    {
        return $this->userService->createUser($username, $password, $email, time());
    }

    protected function doLogic(): void
    {
        $identifier = $this->getConfig('authenticator.unique_identifier');

        $email = $this->getInputVar('email');
        $password = $this->getInputVar('password');
        $password2 = $this->getInputVar('password2');

        if ($identifier == 'email')
        {
            $username = $email;
        }
        else
        {
            $username = $this->getInputVar('username');
        }

        $acceptTerms = $this->getInputVar('accept_terms');

        $this->pageContent['username'] = $this->getRawInputVar('username');
        $this->pageContent['password'] = $password;
        $this->pageContent['password2'] = $password2;
        $this->pageContent['email'] = $this->getRawInputVar('email');
        $this->pageContent['accept_terms'] = $acceptTerms;
        $this->pageContent['recaptcha_site_key'] = $this->getConfig('security.recaptcha.site_key');

        $this->customPreparePageContent();

        // Check if already logged in
        //
        if ($this->isAuthenticated())
        {
            // Redirect to default page
            //
            $returnPage = $this->getConfig('actions.login.default_return_page');
            header('Location: '.$this->getBaseUrl().$returnPage.'?'.
                            $this->getMessageForUrl('info', 'Already logged in'));

            exit();
        }

        // Check if this is a true attempt
        //
        if (!strlen($this->getInputVar('do')))
        {
            return;
        }

        $success = true;

        // Check if required values are present
        //
        if ($identifier == 'username' && !strlen($username))
        {
            $this->addMessage('error', 'Please enter a correct username.', 'Usernames can contain letters, digits and underscores.');
            $success = false;
        }

        if (!strlen($password))
        {
            $this->addMessage('error', 'Please enter a password.', 'Passwords can contain any printable character.');
            $success = false;
        }

        if (!strlen($password2))
        {
            $this->addMessage('error', 'Please enter the password verification.', 'Password verification should match password.');
            $success = false;
        }

        if (strlen($password) && strlen($password2) && $password != $password2)
        {
            $this->addMessage('error', 'Passwords don\'t match.', 'Password and password verification should be the same.');
            $success = false;
        }

        if (strlen($password) < 8)
        {
            $this->addMessage('error', 'Password is too weak.', 'Use at least 8 characters.');
            $success = false;
        }

        if (!strlen($email))
        {
            $this->addMessage('error', 'Please enter a correct e-mail address.', 'E-mail addresses can contain letters, digits, hyphens, underscores, dots and at\'s.');
            $success = false;
        }

        if ($acceptTerms != 1)
        {
            $this->addMessage('error', 'Please accept our Terms.', 'To register for our site you need to accept our Privacy Policy and our Terms of Service.');
            $success = false;
        }

        if ($this->customValueCheck() !== true)
        {
            $success = false;
        }

        $recaptchaResponse = $this->getInputVar('g-recaptcha-response');

        if (!strlen($recaptchaResponse))
        {
            $this->addMessage('error', 'CAPTCHA required', 'To prevent bots registering account en masse, filling in a CAPTCHA is required!');
            $success = false;

            return;
        }

        $recaptcha = new Recaptcha(
            $this->assertService,
            new \GuzzleHttp\Client(),
            $this->getConfig('security.recaptcha.secret_key'),
        );
        $result = $recaptcha->verifyResponse($recaptchaResponse);

        if ($result != true)
        {
            $this->addMessage('error', 'The CAPTCHA code entered was incorrect.');
            $success = false;

            return;
        }

        if (!$success)
        {
            return;
        }

        // Check if identifier already exists
        //
        if ($identifier == 'email')
        {
            $result = $this->query(
                'SELECT id FROM users WHERE email = ?',
                [$email]
            );

            $this->verify($result->RecordCount() <= 1, 'Too many results for email: '.$email);

            if ($result->RecordCount() == 1)
            {
                $forgotPasswordPage = $this->getBaseUrl().$this->getConfig('actions.forgot_password.location');

                $this->addMessage('error', 'E-mail already registered.', 'An account has already been registered with this e-mail address. <a href="'.$forgotPasswordPage.'">Forgot your password?</a>');

                return;
            }
        }
        else
        {
            $result = $this->query(
                'SELECT id FROM users WHERE username = ?',
                [$username]
            );

            $this->verify($result->RecordCount() <= 1, 'Too many results for username: '.$username);

            if ($result->RecordCount() == 1)
            {
                $this->addMessage('error', 'Username already exists.', 'This username has already been taken. Please enter another username.');

                return;
            }
        }

        // Add account
        //
        $result = $this->createUser($username, $password, $email);

        $this->customFinalizeCreate($result);
        $this->postCreateActions($result);
    }

    /**
     * @return never
     */
    protected function postCreateActions(User $user): void
    {
        $code = $this->userEmailService->generateCode($user, 'send_verify', $this->getAfterVerifyData());
        $sendVerifyPage = $this->getConfig('actions.login.send_verify_page');
        $sendVerifyUrl = $sendVerifyPage.'?code='.$code;

        // Redirect to verification request screen
        //
        header('Location: '.$this->getBaseUrl().$sendVerifyUrl);

        exit();
    }

    protected function displayHeader(): void
    {
        echo <<<'HTML'
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
HTML;
    }

    protected function displayContent(): void
    {
        $this->loadTemplate('register_account.tpl', $this->pageContent);
    }
}
