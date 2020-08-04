<?php
class Recaptcha extends FrameworkCore
{
    protected $secret_key;
    protected $error_codes = array();

    function __construct()
    {
        parent::__construct();

        $this->module_config = $this->get_config('security.recaptcha');

        $this->verify(strlen($this->module_config['site_key']), 'Missing reCAPTCHA Site Key');
        $this->verify(strlen($this->module_config['secret_key']), 'Missing reCAPTCHA Secret Key');

        $this->secret_key = $this->module_config['secret_key'];
    }

    function set_secret_key($secret_key)
    {
        $this->secret_key = $secret_key;
    }

    function get_error_codes()
    {
        return $this->error_codes;
    }

    function verify_response($recaptcha_response)
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
