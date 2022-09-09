<?php
class Recaptcha extends FrameworkCore
{
    protected string $secret_key;

    /**
     * @var array<string>
     */
    protected array $error_codes = array();

    /**
     * @var array<string>
     */
    protected array $module_config;

    function __construct()
    {
        parent::__construct();

        $this->module_config = $this->get_config('security.recaptcha');

        $this->verify(strlen($this->module_config['site_key']), 'Missing reCAPTCHA Site Key');
        $this->verify(strlen($this->module_config['secret_key']), 'Missing reCAPTCHA Secret Key');

        $this->secret_key = $this->module_config['secret_key'];
    }

    public function set_secret_key(string $secret_key): void
    {
        $this->secret_key = $secret_key;
    }

    /**
     * @return array<string>
     */
    public function get_error_codes(): array
    {
        return $this->error_codes;
    }

    public function verify_response(string $recaptcha_response): bool
    {
        if (!strlen($recaptcha_response))
            return false;

        $recaptcha_secret = $this->secret_key;
        $this->error_codes = array();

        $recaptcha_data = array(
            'secret' => $recaptcha_secret,
            'response' => $recaptcha_response,
        );

        $verify = curl_init();
        curl_setopt($verify, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
        curl_setopt($verify, CURLOPT_POST, true);
        curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($recaptcha_data));
        curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($verify);
        curl_close($verify);

        if (is_string($response))
            $response = json_decode($response, true);

        if (isset($response['error_codes']))
        {
            $this->error_codes = $response['error_codes'];

            $this->verify(!in_array('invalid-input-secret', $response['error-codes']), 'Invalid reCAPTCHA input secret used');
            $this->verify(!in_array('invalid-keys-secret', $response['error-codes']), 'Invalid reCAPTCHA key used');
        }

        return $response['success'];
    }
};
?>
