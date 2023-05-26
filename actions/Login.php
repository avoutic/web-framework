<?php

namespace WebFramework\Actions;

use WebFramework\Core\BaseFactory;
use WebFramework\Core\ContainerWrapper;
use WebFramework\Core\PageAction;
use WebFramework\Core\Recaptcha;
use WebFramework\Core\User;

class Login extends PageAction
{
    protected string $uniqueIdentifier = '';

    public function init(): void
    {
        parent::init();

        $this->uniqueIdentifier = $this->getConfig('authenticator.unique_identifier');
    }

    public static function getFilter(): array
    {
        $container = ContainerWrapper::get();

        $usernameFormat = FORMAT_USERNAME;
        if ($container->get('authenticator.unique_identifier') == 'email')
        {
            $usernameFormat = FORMAT_EMAIL;
        }

        return [
            'return_page' => FORMAT_RETURN_PAGE,
            'return_query' => FORMAT_RETURN_QUERY,
            'username' => $usernameFormat,
            'password' => FORMAT_PASSWORD,
            'g-recaptcha-response' => '.*',
        ];
    }

    protected function checkSanity(): void
    {
        $loginConfig = $this->getConfig('actions.login');
        $bruteforceProtection = $loginConfig['bruteforce_protection'];

        if ($bruteforceProtection)
        {
            $recaptchaConfig = $this->getConfig('security.recaptcha');
            $this->verify(strlen($recaptchaConfig['site_key']), 'Missing reCAPTCHA Site Key');
            $this->verify(strlen($recaptchaConfig['secret_key']), 'Missing reCAPTCHA Secret Key');
        }
    }

    protected function getTitle(): string
    {
        return 'Login';
    }

    protected function getCanonical(): string
    {
        return $this->pageContent['login_page'];
    }

    protected function getOnload(): string
    {
        return "$('#inputUsername').focus();";
    }

    // Can be overriden for project specific user factories and user classes
    //
    protected function getUser(string $username): User|false
    {
        $factory = $this->container->get(BaseFactory::class);

        return $factory->getUserByUsername($username);
    }

    protected function customValueCheck(User $user): bool
    {
        return true;
    }

    protected function doLogic(): void
    {
        $returnPage = $this->getInputVar('return_page');
        $returnQuery = $this->getInputVar('return_query');

        $this->pageContent['return_query'] = $returnQuery;
        $this->pageContent['username'] = $this->getRawInputVar('username');
        $this->pageContent['recaptcha_needed'] = false;
        $this->pageContent['recaptcha_site_key'] = $this->getConfig('security.recaptcha.site_key');

        if (!strlen($returnPage) || substr($returnPage, 0, 2) == '//')
        {
            $returnPage = $this->getConfig('actions.login.default_return_page');
        }

        if (substr($returnPage, 0, 1) != '/')
        {
            $returnPage = '/'.$returnPage;
        }

        $this->pageContent['return_page'] = $returnPage;
        $this->pageContent['login_page'] = $this->getConfig('actions.login.location');
        $sendVerifyPage = $this->getConfig('actions.login.send_verify_page');

        // Check if already logged in and redirect immediately
        if ($this->isAuthenticated())
        {
            header('Location: '.$this->getBaseUrl().$returnPage.'?'.$returnQuery.'&'.
                            $this->getMessageForUrl('info', 'Already logged in'));

            exit();
        }

        // Check if this is a login attempt
        //
        if (!strlen($this->getInputVar('do')))
        {
            return;
        }

        // Check if username and password are present
        //
        if (!strlen($this->getInputVar('username')))
        {
            if ($this->uniqueIdentifier == 'email')
            {
                $this->addMessage('error', 'Please enter a valid email.');
            }
            else
            {
                $this->addMessage('error', 'Please enter a valid username.');
            }

            return;
        }
        if (!strlen($this->getInputVar('password')))
        {
            $this->addMessage('error', 'Please enter your password.');

            return;
        }

        // Log in user
        //
        $user = $this->getUser($this->getInputVar('username'));

        if ($user === false)
        {
            if ($this->uniqueIdentifier == 'email')
            {
                $this->addMessage('error', 'E-mail and password do not match.', 'Please check if you entered the e-mail and/or password correctly.');
            }
            else
            {
                $this->addMessage('error', 'Username and password do not match.', 'Please check if you entered the username and/or password correctly.');
            }

            $this->addBlacklistEntry('unknown-username');

            return;
        }

        $bruteforceProtection = $this->getConfig('actions.login.bruteforce_protection');
        if ($user->failedLogin > 5 && $bruteforceProtection)
        {
            $recaptchaResponse = $this->getInputVar('g-recaptcha-response');
            $this->pageContent['recaptcha_needed'] = true;

            if (!strlen($recaptchaResponse))
            {
                $this->addMessage('error', 'CAPTCHA required', 'Due to possible brute force attacks on this username, filling in a CAPTCHA is required for checking the password!');

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

                return;
            }
        }

        if (!$user->checkPassword($this->getInputVar('password')))
        {
            $this->addMessage('error', 'Username and password do not match.', 'Please check if you entered the username and/or password correctly.');
            $this->addBlacklistEntry('wrong-password');

            return;
        }

        if ($this->customValueCheck($user) !== true)
        {
            return;
        }

        // Check if verified
        //
        if (!$user->isVerified())
        {
            $code = $user->generateVerifyCode('send_verify');

            $this->addMessage('error', 'Account not yet verified.', 'Account is not yet verified. Please check your mailbox for the verification e-mail and go to the presented link. If you have not received such a mail, you can <a href="'.$this->getBaseUrl().$sendVerifyPage.'?code='.$code.'">request a new one</a>.');

            return;
        }

        // Log in user
        //
        $this->authenticate($user);

        header('Location: '.$this->getBaseUrl().$returnPage.'?'.$returnQuery.'&'.$this->getMessageForUrl('success', 'Login successful.'));

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
        $this->loadTemplate('login.tpl', $this->pageContent);
    }
}
